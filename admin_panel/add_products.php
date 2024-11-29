<?php
include '../components/connect.php';

if (isset($_COOKIE['seller_id'])) {
    $seller_id = $_COOKIE['seller_id'];
} else {
    $seller_id = '';
    header('location:login.php');
}

if (isset($_POST['publish']) || isset($_POST['draft'])) {
    $name = $_POST['name'];
    $name = filter_var($name, FILTER_SANITIZE_STRING);

    $price = $_POST['price'];
    $price = filter_var($price, FILTER_SANITIZE_STRING);

    // Validate price using Regex
    if (!preg_match('/^\d+(\.\d{1,2})?$/', $price)) {
        $warning_msg[] = 'Invalid price format. Please enter a valid number (e.g., 100 or 100.99).';
    } else {
        $price = number_format((float)$price, 2, '.', '');
    }

    $description = $_POST['description'];
    $description = filter_var($description, FILTER_SANITIZE_STRING);

    $stock = $_POST['stock'];
    $stock = filter_var($stock, FILTER_SANITIZE_STRING);

    $status = isset($_POST['publish']) ? 'active' : 'deactive';

    $image = $_FILES['image']['name'];
    $image = filter_var($image, FILTER_SANITIZE_STRING);
    $image_size = $_FILES['image']['size'];
    $image_tmp_name = $_FILES['image']['tmp_name'];
    $image_folder = '../uploaded_files/' . $image;

    $select_image = $conn->prepare("SELECT * FROM `products` WHERE image = ? AND seller_id = ?");
    $select_image->execute([$image, $seller_id]);

    // Handle Bidding Fields
    $enable_bidding = isset($_POST['enable_bidding']) ? 1 : 0;
    $starting_price = $enable_bidding ? $_POST['starting_price'] : null;
    $bidding_end_date = $enable_bidding ? $_POST['bidding_end_date'] : null;

    if ($enable_bidding) {
        $starting_price = filter_var($starting_price, FILTER_SANITIZE_STRING);
        $bidding_end_date = filter_var($bidding_end_date, FILTER_SANITIZE_STRING);

        if (empty($starting_price) || empty($bidding_end_date)) {
            $warning_msg[] = 'Bidding price and end date are required when bidding is enabled.';
        }
    }

    if (isset($image)) {
        if ($select_image->rowCount() > 0) {
            $warning_msg[] = 'Image Name Repeated';
        } elseif ($image_size > 2000000) {
            $warning_msg[] = 'Image Size is Too Large';
        } else {
            move_uploaded_file($image_tmp_name, $image_folder);
        }
    } else {
        $image = '';
    }

    if ($select_image->rowCount() > 0 && $image != '') {
        $warning_msg[] = 'Please Rename Your Image';
    } else {
        $insert_product = $conn->prepare("INSERT INTO `products`(seller_id, name, price, image, stock, product_detail, status, enable_bidding, starting_price, bidding_end_date) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insert_product->execute([$seller_id, $name, $price, $image, $stock, $description, $status, $enable_bidding, $starting_price, $bidding_end_date]);
        $success_msg[] = isset($_POST['publish']) ? 'Product Added Successfully' : 'Product Saved as Draft Successfully';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Figuras D Arte - Admin Add Products Page</title>
    <link rel="stylesheet" type="text/css" href="../css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css">
</head>
<body>
    <div class="main-container">
        <?php include '../components/admin_header.php'; ?>
        <section class="post-editor">
            <div class="heading">
                <h1>Add Products</h1>
                <img src="../image/separator-img.png">
            </div>
            <div class="form-container">
                <form action="" method="post" enctype="multipart/form-data" class="register">
                    <div class="input-field">
                        <p>Product Name <span>*</span></p>
                        <input type="text" name="name" maxlength="100" placeholder="Add Product Name..." required class="box">
                    </div>
                    <div class="input-field">
                        <p>Product Price <span>*</span></p>
                        <input type="number" name="price" maxlength="100" placeholder="Add Product Price..." required class="box">
                    </div>
                    <div class="input-field">
                        <input type="checkbox" name="enable_bidding" id="enable_bidding" onchange="toggleBiddingFields()">
                        <label for="enable_bidding">Enable Bidding</label>
                    </div>
                    <div id="bidding-fields" style="display: none;">
                        <div class="input-field">
                            <label for="starting_price">Starting Price</label>
                            <input type="number" name="starting_price" id="starting_price" min="0" step="0.01" class="box">
                        </div>
                        <div class="input-field">
                            <label for="bidding_end_date">Bidding End Date</label>
                            <input type="datetime-local" name="bidding_end_date" id="bidding_end_date" class="box">
                        </div>
                    </div>
                    <div class="input-field">
                        <p>Product Detail <span>*</span></p>
                        <textarea name="description" required maxlength="1000" placeholder="Add Product Detail..." class="box"></textarea>
                    </div>
                    <div class="input-field">
                        <p>Product Stock <span>*</span></p>
                        <input type="number" name="stock" maxlength="10" min="0" max="9999" placeholder="Add Product Stock..." required class="box">
                    </div>
                    <div class="input-field">
                        <p>Product Image <span>*</span></p>
                        <input type="file" name="image" accept="image/*" required class="box">
                    </div>
                    <div class="flex-btn">
                        <input type="submit" name="publish" value="Add Product" class="btn">
                        <input type="submit" name="draft" value="Save as Draft" class="btn">
                    </div>
                </form>
            </div>
        </section>
    </div>
    <script>
        function toggleBiddingFields() {
            const biddingFields = document.getElementById('bidding-fields');
            const checkbox = document.getElementById('enable_bidding');
            biddingFields.style.display = checkbox.checked ? 'block' : 'none';
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
    <script src="../js/admin_script.js"></script>
    <?php include '../components/alert.php'; ?>
</body>
</html>
