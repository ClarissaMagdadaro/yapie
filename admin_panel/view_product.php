<?php
include '../components/connect.php';

if (isset($_COOKIE['seller_id'])) {
    $seller_id = $_COOKIE['seller_id'];
} else {
    $seller_id = '';
    header('location:login.php');
}

// Handle product deletion
if (isset($_POST['delete'])) {
    $p_id = $_POST['product_id'];
    $p_id = filter_var($p_id, FILTER_SANITIZE_STRING);

    $delete_product = $conn->prepare("DELETE FROM `products` WHERE id = ?");
    $delete_product->execute([$p_id]);

    $success_msg[] = 'Product Deleted Successfully';
}

// Handle toggling of bidding status
if (isset($_POST['toggle_bidding'])) {
    $p_id = $_POST['product_id'];
    $p_id = filter_var($p_id, FILTER_SANITIZE_STRING);
    $bidding_status = $_POST['bidding_enabled'] == '1' ? 0 : 1; // Toggle status

    $update_bidding = $conn->prepare("UPDATE `products` SET bidding_enabled = ? WHERE id = ?");
    $update_bidding->execute([$bidding_status, $p_id]);

    $success_msg[] = 'Bidding status updated successfully!';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Figuras D Arte - Show Products Page</title>
    <link rel="stylesheet" type="text/css" href="../css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
</head>
<body>
    <div class="main-container">
        <?php include '../components/admin_header.php'; ?>
        <section class="show-post">
            <div class="heading">
                <h1>Your Products</h1>
                <img src="../image/separator-img.png" alt="✦ . ⁺ . ✦ . ⁺ . ✦">
            </div>
            <div class="box-container">
                <?php
                $select_products = $conn->prepare("SELECT * FROM `products` WHERE seller_id = ?");
                $select_products->execute([$seller_id]);
                if ($select_products->rowCount() > 0) {
                    while ($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)) {
                ?>
                <form action="" method="post" class="box">
                    <input type="hidden" name="product_id" value="<?= $fetch_products['id']; ?>">
                    <?php if ($fetch_products['image'] != '') { ?>
                        <img src="../uploaded_files/<?= $fetch_products['image']; ?>" class="image"> 
                    <?php } ?>
                    <div class="status" style="color: <?php if ($fetch_products['status'] == 'active') {
                        echo "limegreen"; } else { echo "coral"; } ?>">
                        <?= $fetch_products['status']; ?>
                    </div>
                    <div class="price">₱<?= $fetch_products['price']; ?></div>
                    <div class="content">
                        <div class="title"><?= $fetch_products['name']; ?></div>
                        <div class="flex-btn">
                            <a href="edit_product.php?id=<?= $fetch_products['id']; ?>" class="btn">Edit</a>
                            <button type="submit" name="delete" class="btn" onclick="return confirm('Delete this product?');">Delete</button>
                            <a href="read_product.php?post_id=<?= $fetch_products['id']; ?>" class="btn">Read</a>
                        </div>

                        <!-- Bidding Toggle Section -->
                        <div class="bidding-section">
                            <p>Bidding Enabled: <strong><?= $fetch_products['bidding_enabled'] ? 'Yes' : 'No'; ?></strong></p>
                            <input type="hidden" name="bidding_enabled" value="<?= $fetch_products['bidding_enabled']; ?>">
                            <button type="submit" name="toggle_bidding" class="btn">
                                <?= $fetch_products['bidding_enabled'] ? 'Disable Bidding' : 'Enable Bidding'; ?>
                            </button>
                        </div>
                    </div>
                </form>
                <?php
                    }
                } else {
                    echo '
                        <div class="empty">
                            <p>No Products Added Yet! <br> <a href="add_products.php" class="btn" style="margin-top: 1.5rem;">Add Products</a></p>
                        </div>
                    ';
                }
                ?>
            </div>
        </section>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <script src="../js/admin_script.js"></script>
    <?php include '../components/alert.php'; ?>
</body>
</html>
