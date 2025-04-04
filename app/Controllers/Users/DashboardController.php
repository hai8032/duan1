<?php
class DashboardController
{
    public function dashboard()
    {
        $categoryModel = new CategoryUserModel();
        $listCategory = $categoryModel->getCategoryDB();

        $productModel = new ProductUserModel();
        $listProduct = $productModel->getProductDB();

        include 'app/Views/Users/index.php';
    }

    public function myAccount()
    {
        include 'app/Views/Users/myaccount.php';
    }
    public function accountDetail()
    {
        $userModel = new UserModel2();
        $user = $userModel->getCurrentUser();
        include 'app/Views/Users/account-detail.php';
    }
    public function accountUpdate()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->changePassword();


            $userModel = new UserModel2();
            $user = $userModel->getCurrentUser();
            // Thêm ảnh
            $uploadDir = 'assets/Admin/upload/';
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $destPath = $user->image;

            if (!empty($_FILES['image']['name'])) {
                $fileTmpPath = $_FILES['image']['tmp_name'];
                $fileType = mime_content_type($fileTmpPath);
                $fileName = basename($_FILES['image']['name']);
                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                $newFileName = uniqid() . '.' . $fileExtension;

                if (in_array($fileType, $allowedTypes)) {
                    $destPath = $uploadDir . $newFileName;
                    if (!move_uploaded_file($fileTmpPath, $destPath)) {
                        $destPath = "";
                    }
                    // Xóa ảnh cũ
                    unlink($user->image);
                }
            }

            $userModel = new UserModel2();
            $message = $userModel->updateCurrentUser($destPath);

            if ($message) {
                $_SESSION['message'] = 'Chỉnh sửa thành công';
                header("Location: " . "?&act=account-detail");
                exit;
            } else {
                $_SESSION['message'] = 'Chỉnh sửa không thành công';
                header("Location: " . "?&act=account-detail");
                exit;
            }
        }
    }

    public function changePassword()
    {
        if (
            $_POST['current-password'] != "" &&
            $_POST['new-password'] != "" &&
            $_POST['current-password'] != "" &&
            $_POST['new-password'] == $_POST['confirm-password']
        ) {
            $userModel = new UserModel2();
            $userModel->changePassword($_POST['current-password'], $_POST['new-password']);
        }
    }
    // Giỏ hàng
    public function addToCart()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $cartModel = new CartUserModel();
            $data = $cartModel->addCartModel();
            echo json_encode($data);
        }
    }

    public function showToCart()
    {
        $cartModel = new CartUserModel();
        $data = $cartModel->showCartModel();
        echo json_encode($data);
    }
    public function updateToCart()
    {
        $cartModel = new CartUserModel();
        $data = $cartModel->updateCartModel();
        echo json_encode($data);
    }

    public function shoppingCart()
    {
        $cartModel = new CartUserModel();
        $data = $cartModel->showCartModel();

        include 'app/Views/Users/shopping-cart.php';
    }
    // Thanh toán
    public function checkout()
    {
        $userModel = new UserModel2();
        $currentUser = $userModel->getCurrentUser();

        $cartModel = new CartUserModel();
        $products = $cartModel->showCartModel();

        include 'app/Views/Users/check-out.php';
    }

    public function submitCheckout()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {

            $name = trim($_POST['name']);
            $address = trim($_POST['address']);
            $phone = trim($_POST['phone']);
            $email = trim($_POST['email']);

            $errors = [];

            // Kiểm tra hợp lệ
            if (empty($name)) {
                $errors[] = "Vui lòng nhập họ tên!";
            }
            if (empty($address)) {
                $errors[] = "Vui lòng nhập địa chỉ!";
            }
            if (!preg_match('/^[0-9]{9,15}$/', $phone)) {
                $errors[] = "Số điện thoại không hợp lệ! Vui lòng nhập số hợp lệ (9-15 chữ số).";
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Email không hợp lệ! Vui lòng nhập đúng định dạng.";
            }
            
            if (!empty($errors)) {
                $_SESSION['errors'] = $errors;
                header("Location: ?act=check-out");
                exit();
            }

            $cartModel = new CartUserModel();
            $products = $cartModel->showCartModel();

            $orderModel = new OrderUserModel();
            $addOrder = $orderModel->order($products);

            if ($addOrder) {
                $cartModel->deleteCartDetail();
                session_start();
                $_SESSION['success_message'] = "Mua hàng thành công!";
                header("Location: index.php");
                exit();
            }
        }
    }

    public function showShop()
    {
        $productModel = new ProductUserModel();
        $listProduct = $productModel->getDataShop();

        $categoryModel = new CategoryUserModel();
        if (isset($_GET['category_id'])) {
            $category = $categoryModel->getCategoryById($_GET['category_id']);
        }

        $listCategory = $categoryModel->getCategoryDB();
        $stock = $productModel->getProductStock();

        if (isset($_GET['instock'])) {
            $listProduct = array_filter($listProduct, function ($product) {
                return $product->stock > 0;
            });
        }

        if (isset($_GET['outstock'])) {
            $listProduct = array_filter($listProduct, function ($product) {
                return $product->stock == 0;
            });
        }

        if (isset($_GET['min'])) {
            $listProduct = array_filter($listProduct, function ($product) {
                if ($product->price_sale != null) {
                    return $product->price_sale >= $_GET['min'];
                }
                return $product->price > $_GET['min'];
            });
        }

        if (isset($_GET['max'])) {
            $listProduct = array_filter($listProduct, function ($product) {
                if ($product->price_sale != null) {
                    return $product->price_sale <= $_GET['max'];
                }
                return $product->price < $_GET['max'];
            });
        }

        if (isset($_GET['product-name'])) {
            $listProduct = $productModel->getDataShopName();
        }

        include 'app/Views/Users/shop.php';
    }

    public function productDetail()
    {
        $productModel = new ProductUserModel();
        $product = $productModel->getProductById();

        // Kiểm tra nếu sản phẩm không tồn tại
        if (!$product) {
            die("Sản phẩm không tồn tại!");
        }

        $productImage = $productModel->getProductImageById();
        $otherProduct = $productModel->getOtherProduct($product->category_id, $product->id);
        $comment = $productModel->getComment($product->id);
        foreach ($comment as $key => $value) {
            $rating = $productModel->getCommentByUser($product->id, $value->user_id);
            if ($rating) {
                $comment[$key]->rating = $rating->rating;
            } else {
                $comment[$key]->rating = null;
            }
        }

        $ratingProduct = $productModel->getRating($product->id);


        include 'app/Views/Users/product-detail.php';
    }

    public function writeReview()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $productModel = new ProductUserModel();
            $productModel->saveRating();
            $productModel->saveComment();
        }
        header("Location:?act=product-detail&product_id=" . $_POST['productId']);
    }

    public function showOrder()
    {
        $orderModel = new OrderUserModel();
        $orders = $orderModel->getAllOrder();
        include 'app/Views/Users/show-order.php';
    }
    public function showOrderDetail()
    {
        $orderModel = new OrderUserModel();
        $order_detail = $orderModel->getOrderDetail();
        include 'app/Views/Users/show-order-detail.php';
    }
    public function cancelOrder()
    {
        $orderModel = new OrderUserModel();
        $orderModel->cancelOrderModel();
        header("Location:?act=show-order");
    }
}
