# Validation of InputObjects (or Objects)

You can validate lists of things. You can specify a `validate` callback on the `ListOf` field itself, ... TODO fill this
in. Any errors returned on the list items will each have an `index` property so you will know exactly which items were
invalid.

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
  savePhoneNumbers(phoneNumbers: ["123-3456", "867-5309"]) {
    __valid
    __code
    __msg
    phoneNumbers {
      items {
        __code
        __msg
        __path
      }
    }
  }
}
```

### Try mutation with invalid input

```
mutation {
  savePhoneNumbers(phoneNumbers: ["123-3456", "xxx-5309"]) {
    __valid
    __code
    __msg
    phoneNumbers {
      items {
        __code
        __msg
        __path
      }
    }
  }
}
```