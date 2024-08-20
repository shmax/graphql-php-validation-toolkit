# Simple scalar validation
The simplest possible type of user input validation. Mutation expects an `authorId` arg, and will respond with a nicely formatted error code and message if an author by that id doesn't exist.

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
  deleteAuthor(id: 1) {
    valid
    result
  }
}
```

### Try mutation with invalid input
```
mutation {
  deleteAuthor(id: 3) {
    valid
    result
    suberrors {
      id {
        code
        msg
      }
    }
  }
}
```