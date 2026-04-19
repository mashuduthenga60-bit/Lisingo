<?php
require_once __DIR__ . '/functions.php';
$cart_count = getCartCount();
$unread_count = getUnreadCount();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar bg-dark text-white py-1 d-none d-md-block">
        <div class="container d-flex justify-content-between align-items-center">
            <small><i class="bi bi-geo-alt"></i> South Africa | <i class="bi bi-envelope"></i> support@lisingo.co.za</small>
            <small><i class="bi bi-telephone"></i> +27 12 345 6789</small>
        </div>
    </div>

    <!-- Main Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="<?php echo SITE_URL; ?>/">
                <i class="bi bi-shop"></i> <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <!-- Search Bar -->
                <form class="d-flex mx-auto my-2 my-lg-0 search-form" action="<?php echo SITE_URL; ?>/pages/search.php" method="GET">
                    <div class="input-group">
                        <input type="text" class="form-control" name="q" placeholder="Search products..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>

                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/products.php"><i class="bi bi-grid"></i> Browse</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/sell.php"><i class="bi bi-plus-circle"></i> Sell</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>/pages/messages.php">
                                <i class="bi bi-chat-dots"></i>
                                <?php if ($unread_count > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $unread_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>/pages/cart.php">
                                <i class="bi bi-cart3"></i>
                                <?php if ($cart_count > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $cart_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/profile.php"><i class="bi bi-person"></i> My Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/my_products.php"><i class="bi bi-box"></i> My Products</a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/pages/orders.php"><i class="bi bi-receipt"></i> My Orders</a></li>
                                <?php if (isAdmin() || isModerator()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-primary" href="<?php echo SITE_URL; ?>/admin/"><i class="bi bi-speedometer2"></i> Admin Panel</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/pages/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/pages/login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary btn-sm ms-2" href="<?php echo SITE_URL; ?>/pages/register.php">Sign Up</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <div class="container mt-3">
        <?php echo displayFlashMessage(); ?>
    </div>

    <!-- Main Content -->
    <main>
