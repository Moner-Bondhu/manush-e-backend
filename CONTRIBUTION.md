# Contributing to Manush-E Backend

Thank you for considering contributing to the Manush-E backend API! Your help is deeply appreciated — whether it’s bug fixes, feature improvements, or documentation updates.

---

## 📌 How to Contribute

1. **Fork the Repository**  
   Use the "Fork" button to create your own copy.

2. **Clone Your Fork**
   ```bash
   git clone <your-fork-url>
   cd manush-e-backend
   ```

3. **Create a Branch**
    ```bash
    git checkout -b FEATURE_your-feature-name
    ```

4. **Make Your Changes**
    Ensure your code follows Laravel and PSR-12 standards.

5. **Run Tests Locally**
    ```bash
    php artisan test
    ```
    
6. **Commit and Push**
    ```bash
    git add .
    git commit -m "Add: short description of change"
    git push origin FEATURE_your-feature-name
    ```

7. **Open a Pull Request**
Describe your changes clearly and reference related issues if applicable.

---

## 🧪 Code Standards & Testing
* Follow PSR-12 standards. You can auto-format using Laravel Pint:
    ```bash
    ./vendor/bin/pint
    ```

* Run existing tests using:
    ```bash
    php artisan test
    ```
    You can also run with:

    ```bash
    vendor/bin/phpunit
    ```

* Include new tests for your features where applicable.

* For test coverage (requires phpdbg or xdebug): (Optional)

```bash
phpdbg -qrr ./vendor/bin/phpunit --coverage-html coverage
```

---

## 📝 Documentation
* Update the README or relevant docs if your changes affect setup or behavior.

* Follow consistent and clear commenting in your code.

---

## 📄 Contributor License Agreement (CLA)
By submitting a contribution, you affirm that you have the right to, and do, license your contribution under the project’s open source license.

## 🧠 Code of Conduct
* Please check our [Code of Conduct](https://manush-e-docs.monerbondhu.com/code-of-conduct/) documentation before interacting with our repositories.
* All interactions should be respectful and inclusive.

## 🙌 Need Help?
Look for issues labeled good first issue or help wanted

You can also reach out via email at [info@monerbondhu.com](mailto:info@monerbondhu.com)
