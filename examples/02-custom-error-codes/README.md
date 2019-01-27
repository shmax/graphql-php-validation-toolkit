# Simple scalar validation
If you supply an `errorCodes` property on your `fields` or `args` definitions, then a custom, unique error code type will be created.

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
  updateAuthor(authorId:1) {
    suberrors {
      authorId {
        code
        msg
      }
    }
    result {
      id
      name
    }
    valid
  }
}
```

### Try mutation with invalid input
```
mutation {
  updateAuthor(authorId:999) {
    suberrors {
      authorId {
        code
        msg
      }
    }
    result {
      id
      name
    }
    valid
  }
}
```