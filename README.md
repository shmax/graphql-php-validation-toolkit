# graphql-php-validation-toolkit
[![License](https://poser.pugx.org/shmax/graphql-php-validation-toolkit/license)](https://packagist.org/packages/shmax/graphql-php-validation-toolkit)
[![Static Analysis](https://github.com/shmax/graphql-php-validation-toolkit/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/shmax/graphql-php-validation-toolkit/actions/workflows/static-analysis.yml)
[![Coverage Status](https://codecov.io/gh/shmax/graphql-php-validation-toolkit/branch/master/graph/badge.svg)](https://codecov.io/gh/shmax/graphql-php-validation-toolkit/branch/master)
[![Latest Stable Version](https://poser.pugx.org/shmax/graphql-php-validation-toolkit/version)](https://packagist.org/packages/shmax/graphql-php-validation-toolkit)

GraphQL is great when it comes to validating types and checking syntax, but isn't much help when it comes to providing additional validation on user input. The authors of GraphQL have generally opined that the correct response to bad user input is not to throw an exception, but rather to return any validation feedback along with the result.

As Lee Byron explains [here](https://github.com/facebook/graphql/issues/117#issuecomment-170180628):

> ...allow for data for a user-facing report in the payload of your mutation.  It's often the case that mutation payloads include a "didSucceed" field and a "userError" field. If your UI requires rich information about potential errors, then you should include this information in your payload as well.

That's where this small library comes in.

`graphql-php-validation-toolkit` extends the built-in definitions provided by the wonderful [graphql-php](https://github.com/webonyx/graphql-php) library with a new `ValidatedFieldDefinition` class. Simply instantiate one of these in place of the usual field config, add `validate` callback properties to your `args` definitions, and the `type` of your field will be replaced by a new, dynamically-generated `ResultType` with queryable error fields for each of your args. It's a recursive process, so your `args` can have `InputObjectType` types with subfields and `validate` callbacks of their own. Your originally-defined `type` gets moved to the `result` field of the generated type.

## Installation
Via composer:
```
composer require shmax/graphql-php-validation-toolkit 
```

## Documentation

- [Basic Usage](#basic-usage)
- [The Validate Callback](#the-validate-callback)
- [Custom Error Codes](#custom-error-codes)
- [Managing Created Types](#managing-created-types)
- [Examples](#examples)

### Basic Usage
In a nutshell, replace your usual vanilla field definition with an instance of `ValidatedFieldDefinition`, and add `validate` callbacks to one or more of the `args` configs. Let's say you want to make a mutation called `updateBook`:
 
 ```php
 //...
'updateBook' => new ValidatedFieldDefinition([
    'name' => 'updateBook',
    'type' => Types::book(),
    'args' => [
        'bookId' => [
            'type' => Type::id(),
            'validate' => function ($bookId) {
                global $books;
                if (!Book::find($bookId) {
                    return 0;
                }

                return [1, 'Unknown book!'];
            },
        ],
    ],
    'resolve' => static function ($value, $args) : bool {
        return Book::find($args['bookId']);
    },
],
```
 
In the sample above, the `book` type property of your field definition will be replaced by a new dynamically-generated type called `UpdateBookResultType`.

The type generation process is recursive, traveling down through any nested `InputObjectType` or `ListOf` types and checking their `fields` for more `validate` callbacks. Every field definition--including the very top one--that has a `validate` callback will be represented by a custom, generated type with the following queryable fields:

| Field       | Type                          | Description                                                                                                                                           |
|-------------|-------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------|
| `code`      | `int` &vert; `<field-name>ErrorCode` | This will resolve to `0` for a valid field, otherwise `1`. If `errorCodes` were provided, then this will be a custom generated Enum type.             |
| `msg`       | `string`                      | A plain, natural language description of the error.                                                                                                   |
| `suberrors` | `<field-name>_Suberrors`      | A `suberrors` field will be added to a generated field of type `InputObjectType` if any of the following are true: <ol><li>It is the root node</li><li>The field has a `validate` method</li><li>The `InputObjectType` is wrapped in a `ListOfType`</li></ol> 

The top-level `<field-name>ResultType` will have a few additional fields:

| Field       | Type                          | Description                                                                                                                                           |
|-------------|-------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------|
| `valid` | `bool` | Resolves to `true` if all `args` and nested `fields` pass validation, `false` if not. |
| `result` | `mixed` | This is the original `type` you provided when declaring your field. Eg, If you specified `type` to be a `Book`, then the type of `result` will be `Book`. |

You can then simply query for these fields along with `result`:
```graphql
mutation {
    updateAuthor(
        authorId: 1
  ) {
    valid
    result {
        id
        name
    }
    code
    msg
    suberrors {
        authorId {
            code
            msg
        }
    }
  }
}
```

### The Validate Callback

Any field definition can have a `validate` callback. The first argument passed to the `validate` callback will be the value to validate. 
If the value is valid, return `0`, otherwise `1`. 

```php
//...
'updateAuthor' => new ValidatedFieldDefinition([
  'type' => Types::author(),
  'args' => [
    'authorId' => [
      'validate' => function(string $authorId) {
        if(Author::find($authorId)) {
          return 0;
        }
        return 1;
      }
    ]
  ]	  
])
```

If you want to return an error message, return an array with the message in the second bucket:
```php
//...
'updateAuthor' => new ValidatedFieldDefinition([
  'type' => Types::author(),
  'args' => [
    'authorId' => [
      'validate' => function(string $authorId) {
        if(Author::find($authorId)) {
          return 0;
        }
        return [1, "We can't find that author"];
      }
    ]
  ]	  
])
```

Generated `ListOf` error types also have a `path` field that you can query so you can know the exact address in the multidimensional array of each item that failed validation:

```php
//...
'setPhoneNumbers' => new ValidatedFieldDefinition([
  'type' => Types::bool(),
  'args' => [
    'phoneNumbers' => [
      'type' => Type::listOf(Type::string()),  
      'validate' => function(string $phoneNumber) {
        $res = preg_match('/^[0-9\-]+$/', $phoneNumber) === 1;
        if (!$res) {
          return [1, 'That does not seem to be a valid phone number'];
        }
        return 0;  
      }
    ]
  ]	  
])  
```

### Custom Error Codes

If you would like to use custom error codes, add an `errorCodes` property at the same level as your `validate` callback:

```php
//...
'updateAuthor' => [
  'type' => Types::author(),
  'errorCodes' => [
    'authorNotFound`
  ],
  'validate' => function(string $authorId) {
    if(Author::find($authorId)) {
      return 1;
    }
    return ['authorNotFound', "We can't find that author"];
  }
]   
```


### Managing Created Types
This library will create new types as needed. If you are using some kind of type manager to store and retrieve types, you can integrate it by providing a `typeSetter` callback:

```php
new ValidatedFieldDefinition([
    'typeSetter' => static function ($type) {
        Types::set($type);
    },
]);
``` 
 
## Examples
The best way to understand how all this works is to experiment with it. There are a series of increasingly complex one-page samples in the `/examples` folder. Each is accompanied by its own `README.md`, with instructions for running the code. Run each sample, and be sure to inspect the dynamically-generated types in [ChromeiQL](https://chrome.google.com/webstore/detail/chromeiql/fkkiamalmpiidkljmicmjfbieiclmeij?hl=en).

01. [basic-scalar-validation](./examples/01-basic-scalar-validation)
02. [custom-error-types](./examples/02-custom-error-codes)
03. [input-object-validation](./examples/03-input-object-validation)
03. [list-of-validation](./examples/04-list-of-validation)

## Contribute
Contributions are welcome. Please refer to [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.

