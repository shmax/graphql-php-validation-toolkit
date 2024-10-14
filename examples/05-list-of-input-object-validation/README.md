# Validation of InputObjects (or Objects)

You can add validate lists of compound types, such as InputObject. You can specify a `validate` callback on the `ListOf`
field to be applied to each item in the list, and if the compound object has any `validate` callbacks on its own fields,
they will be called as well.

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
  updateAuthors(
    authors: [{
      id:1,
      firstName: "Stephen",
      lastName: "King"
    },{
      id:2,
      firstName: "Arthur",
      lastName: "Clarke"
    }]
  ) {
    __valid
    authors {
      items {
        id {
          __code
          __msg
        }
        firstName {
          __code
          __msg
        }
        lastName {
          __code
          __msg
        }
      }
    }
    __result {
      firstName
      lastName
    }
  }
}
```

### Try mutation with invalid input

```
mutation {
	updateAuthors(
    authors: [{
      id:1,
      firstName: "Richard",
      lastName: "Matheson"
    },{
      id:2,
      firstName: "Diana",
      lastName: "Jones"
    }]
  ) {
    __valid
    authors {
      items {
        path
        id {
          __code
          __msg
        }
        firstName {
          __code
          __msg
        }
        lastName {
          __code
          __msg
        }
      }
    }
    __result {
      firstName
      lastName
    }
  }
}
```