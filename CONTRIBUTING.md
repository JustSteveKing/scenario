# Contributing to Scenario

Thank you for considering contributing to **Scenario**! We welcome all contributions that help improve this orchestration engine.

## 🤝 Code of Conduct

By participating in this project, you agree to abide by the same professional standards we apply to our code: be respectful, constructive, and collaborative.

## 🛠️ Development Setup

To get started with development:

1. **Fork the repository** on GitHub.
2. **Clone your fork** to your local machine.
3. **Install dependencies** using Composer:
   ```bash
   composer install
   ```

## 📜 Coding Standards

We maintain a strict set of coding standards to ensure the library remains maintainable and reliable.

### Linting (Laravel Pint)
We use [Laravel Pint](https://laravel.com/docs/pint) to enforce a consistent code style. Before submitting a Pull Request, please run:
```bash
composer lint
```
To automatically fix any style issues:
```bash
composer pint
```

### Static Analysis (PHPStan)
All code must pass PHPStan analysis at **Level 10** with strict rules. To check your changes:
```bash
composer stan
```

## 🧪 Testing

We aim for high test coverage (95%+). Every new feature or bug fix **must** include corresponding tests.

To run the full test suite:
```bash
composer test
```

To run with coverage (requires Xdebug or PCOV):
```bash
XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-text
```

## 🚀 Pull Request Process

1. **Branching**: Create a new branch for your feature or fix (e.g., `feature/my-new-feature` or `fix/issue-description`).
2. **Tests**: Ensure all existing and new tests pass.
3. **Analysis**: Ensure `composer stan` and `composer lint` report zero errors.
4. **Description**: Provide a clear description of the changes in your Pull Request. Reference any related issues.
5. **Review**: Once submitted, a maintainer will review your PR. We may suggest some changes before merging.

## 🐞 Reporting Bugs

If you find a bug, please open an issue on GitHub. Include:
- A clear description of the bug.
- Steps to reproduce the behavior.
- Expected vs. actual results.
- Your PHP version and environment details.

---

Thank you for making **Scenario** better!
