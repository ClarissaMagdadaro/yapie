<?php
include 'components/connect.php';

if (isset($_COOKIE['user_id'])) {
    $user_id = $_COOKIE['user_id'];
} else {
    $user_id = '';
}

include 'components/add_wishlist.php';
include 'components/add_cart.php';

// Handle the bidding process
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_bid'])) {
    $product_id = $_POST['product_id'];
    $bid_amount = $_POST['bid_amount'];

    // Check if user is logged in
    if (!$user_id) {
        echo "<script>alert('Please log in to place a bid.'); window.location.href='login.php';</script>";
        exit;
    }

    // Fetch the product's seller_id
    $product_query = $conn->prepare("SELECT seller_id FROM products WHERE id = ?");
    $product_query->execute([$product_id]);
    $product = $product_query->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $seller_id = $product['seller_id']; // Correct seller_id

        // Get the current highest bid for the product
        $highest_bid_query = $conn->prepare("SELECT MAX(bid_amount) AS highest_bid FROM bids WHERE product_id = ?");
        $highest_bid_query->execute([$product_id]);
        $highest_bid = $highest_bid_query->fetchColumn();

        // Check if the bid is valid
        if ($bid_amount > ($highest_bid ?? 0)) {
            // Insert the new bid into the database
            $insert_bid = $conn->prepare("INSERT INTO bids (product_id, user_id, bid_amount, seller_id) VALUES (?, ?, ?, ?)");
            $insert_bid->execute([$product_id, $user_id, $bid_amount, $seller_id]);

            echo "<script>alert('Bid placed successfully!'); window.location.reload();</script>";
        } else {
            echo "<script>alert('Your bid must be higher than the current highest bid!');</script>";
        }
    } else {
        echo "<script>alert('Product not found!');</script>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Figuras D' Arte - Shop Page</title>
    <link rel="stylesheet" type="text/css" href="css/user_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel='stylesheet'>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@100..900&display=swap" rel="stylesheet">
</head>
<body>
<?php include 'components/user_header.php'; ?>
<div class="products">
    <div class="heading">
        <h1>Our Merchandise</h1>
        <img src="image/separator-img.png">
    </div>
    <div class="box-container">
        <?php
        $select_products = $conn->prepare("SELECT * FROM `products` WHERE status = ?");
        $select_products->execute(['active']);

        if ($select_products->rowCount() > 0) {
            while ($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)) {
        ?>
        <form action="" method="post" class="box <?php if ($fetch_products['stock'] == 0) { echo "disabled"; } ?>">
            <img src="uploaded_files/<?= $fetch_products['image']; ?>" class="image">
            
            <?php if ($fetch_products['stock'] > 9) { ?>
                <span class="stock" style="color: green;">In Stock</span>
            <?php } elseif ($fetch_products['stock'] == 0) { ?>
                <span class="stock" style="color: red;">Out of Stock</span>
            <?php } else { ?>
                <span class="stock" style="color: red;">Hurry, only <?= $fetch_products['stock']; ?></span>
            <?php } ?>
            <div class="content">
                <div class="button">
                    <div><h3 class="name"><?= $fetch_products['name']; ?></h3></div>
                    <div>
                        <button type="submit" name="add_to_cart"><i class="bx bx-cart"></i></button>
                        <button type="submit" name="add_to_wishlist"><i class="bx bx-heart"></i></button>
                        <a href="view_page.php?pid=<?= $fetch_products['id'] ?>" class="bx bxs-show"></a>
                    </div>
                </div>
                <p class="price">Price ₱<?= $fetch_products['price']; ?></p>
                <input type="hidden" name="product_id" value="<?= $fetch_products['id'] ?>">

                <!-- Buy Now Button -->
                <div class="flex-btn">
                    <a href="checkout.php?get_id=<?= $fetch_products['id'] ?>" class="btn">Buy Now</a>
                    <input type="number" name="qty" required min="1" value="1" max="<?= $fetch_products['stock']; ?>" maxlength="2" class="qty box">
                </div>

                <!-- Place Bid Section (Shown Only if Bidding is Enabled) -->
                <?php if ($fetch_products['bidding_enabled'] == 1) { ?>
                <div class="bidding-section">
                    <?php
                    $highest_bid_query = $conn->prepare("SELECT MAX(bid_amount) AS highest_bid FROM bids WHERE product_id = ?");
                    $highest_bid_query->execute([$fetch_products['id']]);
                    $highest_bid = $highest_bid_query->fetchColumn();
                    ?>
                    <p>Highest Bid: ₱<?= $highest_bid ?? '0.00'; ?></p>
                    <form action="" method="POST">
                        <input type="hidden" name="product_id" value="<?= $fetch_products['id']; ?>">
                        <label for="bid_amount">Your Bid (₱):</label>
                        <input type="number" name="bid_amount" id="bid_amount" min="<?= ($highest_bid ?? 0) + 1; ?>" required>
                        <button type="submit" name="place_bid" class="btn">Place Bid</button>
                    </form>
                </div>
                <?php } ?>
            </div>
        </form>
        <?php
            }
        } else {
            echo '
                <div class="empty">
                    <p>No Products Added Yet!</p>
                </div>
            ';
        }
        ?>
    </div>
</div>
<?php include 'components/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<script src="js/user_script.js"></script>
<?php include 'components/alert.php'; ?>
</body>
</html>
