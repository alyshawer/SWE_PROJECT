# Testing Guide - Local & GitHub Actions

## Yes, you can run tests both locally and on GitHub Actions simultaneously!

Both testing environments work independently and complement each other:
- **Local Testing**: Run tests on your machine during development
- **GitHub Actions**: Automatically runs tests when you push code

---

## 🔧 Local Testing (Windows/XAMPP)

### Prerequisites
- PHP 8.2+ installed (via XAMPP)
- Composer installed
- Dependencies installed (`composer install`)

### Running Tests Locally

#### Option 1: Using Composer (Recommended)
```powershell
composer test
```

#### Option 2: Direct PHPUnit Command
```powershell
# Using XAMPP PHP
& "C:\xampp\php\php.exe" "vendor\bin\phpunit" --colors=always

# Or if PHP is in PATH
php vendor\bin\phpunit --colors=always
```

#### Option 3: With Detailed Output
```powershell
& "C:\xampp\php\php.exe" "vendor\bin\phpunit" --testdox --colors=always
```

#### Option 4: Run Specific Test File
```powershell
& "C:\xampp\php\php.exe" "vendor\bin\phpunit" tests/AuthControllerTest.php
```

#### Option 5: Generate HTML Report
```powershell
& "C:\xampp\php\php.exe" "vendor\bin\phpunit" --testdox-html test-report.html
```

### Local Test Configuration
- **Database**: SQLite in-memory (`sqlite::memory:`)
- **Config File**: `phpunit.xml`
- **Bootstrap**: `tests/bootstrap.php`
- **Test Directory**: `tests/`

---

## 🚀 GitHub Actions Testing

### Automatic Execution
Tests run automatically when:
- You push code to `main` branch
- You create a pull request to `main` branch

### Workflow File
- **Location**: `.github/workflows/phpunit.yml`
- **Runs on**: Ubuntu latest
- **PHP Version**: 8.2
- **Database**: SQLite in-memory (same as local)

### Viewing Test Results
1. Go to your GitHub repository
2. Click **"Actions"** tab
3. Select the workflow run
4. View test results and logs

### Manual Trigger
You can also manually trigger tests:
- Go to Actions tab
- Select "PHP Unit Tests" workflow
- Click "Run workflow"

---

## 📊 Comparison: Local vs GitHub Actions

| Feature | Local Testing | GitHub Actions |
|---------|--------------|----------------|
| **When** | Anytime (manual) | Automatic on push/PR |
| **Environment** | Your Windows machine | Ubuntu Linux |
| **PHP Version** | Your installed version | PHP 8.2 (configured) |
| **Database** | SQLite in-memory | SQLite in-memory |
| **Speed** | Instant | ~10-30 seconds |
| **Purpose** | Development feedback | CI/CD verification |
| **Cost** | Free | Free (public repos) |

---

## ✅ Best Practices

### 1. **Test Locally Before Pushing**
```powershell
# Always run tests locally first
composer test

# If tests pass, then push
git add .
git commit -m "Your changes"
git push origin main
```

### 2. **Use GitHub Actions for Verification**
- GitHub Actions confirms tests pass on a clean environment
- Catches environment-specific issues
- Provides test history and reports

### 3. **Both Use Same Configuration**
- Same `phpunit.xml` file
- Same test files in `tests/` directory
- Same SQLite in-memory database
- Same test assertions

---

## 🔍 Troubleshooting

### Local Tests Fail but GitHub Passes
- Check PHP version compatibility
- Ensure all dependencies are installed: `composer install`
- Verify PHP extensions are enabled (mbstring, pdo_sqlite, etc.)

### GitHub Tests Fail but Local Passes
- Check workflow file syntax (`.github/workflows/phpunit.yml`)
- Verify all files are committed and pushed
- Check GitHub Actions logs for specific error messages

### Tests Work Differently
- Both should produce identical results
- If different, check for environment-specific code
- Ensure no hardcoded paths or Windows-specific code

---

## 📝 Quick Commands Reference

### Local Testing
```powershell
# Install dependencies (first time only)
composer install

# Run all tests
composer test

# Run with detailed output
vendor\bin\phpunit --testdox --colors=always

# Run specific test
vendor\bin\phpunit tests/AuthControllerTest.php

# Generate HTML report
vendor\bin\phpunit --testdox-html test-report.html
```

### GitHub Actions
```bash
# Just push your code - tests run automatically!
git push origin main

# Or create a pull request
git checkout -b feature-branch
git push origin feature-branch
# Create PR on GitHub - tests run automatically
```

---

## 🎯 Summary

**You can and should use both!**

1. **During Development**: Run tests locally for fast feedback
2. **Before Committing**: Run `composer test` to catch issues early
3. **After Pushing**: GitHub Actions automatically verifies your code
4. **On Pull Requests**: Tests run automatically for code review

Both environments are configured to use the same test suite, so results should be consistent. If they differ, it indicates an environment-specific issue that needs fixing.

---

## 📚 Additional Resources

- **PHPUnit Documentation**: https://phpunit.de/documentation.html
- **GitHub Actions Docs**: https://docs.github.com/en/actions
- **Project README**: See `README.md` for more project details

