<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="/assets/css/style.css" />
  <?php if (isset($extra_css)) echo $extra_css; ?>
  <title><?= $page_title ?? 'SegreDuino Admin' ?></title>
</head>
<body>
  
  <?php require_once __DIR__ . '/sidebar.php'; ?>

  <section id="content">
    
    <nav>
      <i class="bx bx-menu"></i>
      <a href="#" class="nav-link">Categories</a>
      <form action="#">
        <div class="form-input">
          <input type="search" placeholder="Search..." />
          <button type="submit" class="search-btn">
            <i class="bx bx-search"></i>
          </button>
        </div>
      </form>
      <a href="#" class="notification">
        <i class="bx bxs-bell"></i>
        <span class="num">0</span>
      </a>
      <a href="#" class="profile">
        <img src="/assets/img/pdm logo.jfif" alt="profile" />
      </a>
    </nav>
    ```

---

### 4. `views/layouts/footer.php`
Closes out the layout smoothly.

```php
</section> <script src="/assets/js/script.js"></script>
  
  <?php if (isset($extra_js)) echo $extra_js; ?>

</body>
</html>