# Contributing to GraphQL PHP Validation Toolkit
PRs and suggestions are welcome.

## Workflow

### Install
1. Fork this repo
2. clone it into your repos directory and navigate to that directory:
    ```
    git clone https://github.com/shmax/graphql-php-validation-toolkit.git
    cd graphql-php-validation-toolkit
    ```
3. Install dependencies:
    ```
    composer install
    ```

### Iterate
1. Make your changes
2. Add one or more tests to the `/tests` folder to affirm your changes
3. Execute `composer run test`.
4. Execute `composer run lint` to make sure there are no style issues.
5. If there are a lot of style isues, you can run `composer run fix-style` to automatically fix them.
5. Repeat 1-4 until the code does what you want, all tests pass, and there are no style issues. To save time, you can run `composer check-all`

### Submit
1. Commit your changes and push your branch
2. Open a PR    