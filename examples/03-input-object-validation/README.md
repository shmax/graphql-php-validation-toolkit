# Validation of InputObjects (or Objects)

You can add `validate` properties to the fields of nested Objects or InputObjects (and those fields can themselves be of
complex types with their own fields, and so on). This library will sniff them out and recursively build up a result type
with a similarly nested structure.

### Run locally

```
php -S localhost:8000 ./index.php
```

### Install ChromeiQL plug-in for Chrome

1. Install from [here](https://chrome.google.com/webstore/detail/chromeiql/fkkiamalmpiidkljmicmjfbieiclmeij?hl=en)
2. Enter "http://localhost:8000" in the text field at the top and press the "Set Endpoint" button
3. Be sure to inspect the "Docs" flyout to get familiar with the dynamically-generated types

### Try mutation with valid input

```
mutation {
	updateAuthor(
    authorId: 1
  	attributes: {
      name: "Stephen King",
      age: 47
    }
  ) {
	_result {
      id
      name
    }
    attributes {
      _code
      _msg
        age {
          _code
          _msg
        }
        name {
          _code
          _msg
        }
    }
  }
}
```

### Try mutation with invalid input

```
mutation {
	updateAuthor(
    authorId: 1
  	attributes: {
      name: "Edward John Moreton Drax Plunkett, 18th Baron of Dunsany",
      age: -3
    }
  ) {
    _result {
      id
      name
    }
    attributes {
      _code
      _msg
      age {
        _code
        _msg
      }
      name {
        _code
        _msg
      }
    }
  }
}
```