<?php
require_once(__DIR__ . '/../models/Restaurant.php');
require_once(__DIR__ . '/../models/User.php');
require_once(__DIR__ . '/../controllers/RememberTokenController.php');
if (isset($_SESSION['user'])) {
    $user = $_SESSION['user'];
    $userId = $user['id'];
    $userName = $user['name'];
}

class RestaurantController
{
    var $model;
    var $user;
    var $user_id;
    var $user_name;

    function __construct()
    {
        $this->model = new Restaurant();
        $this->user = new User();

        $rememberController = new RememberTokenController();
        if ($rememberController->isUserLoggedIn()) {
            $this->user_id = $_SESSION['user']['id'];
            $this->user_name = $_SESSION['user']['name'];
        } else {
            header("Location: ?mod=auth&act=viewLogin");
        }
    }

    function list()
    {
        $restaurants = $this->model->list();
        $user_name = $this->user_name;
        require_once(__DIR__ . '/../views/Restaurant/list.php');
    }

    function detail()
    {
        $id = $_GET['id'];
        $restaurant = $this->model->findById($id);
        $user = $this->user->findById($restaurant['user_id']);
        $isEditable = false;
        if ($this->isAuthor($id)) {
            $isEditable = true;
        }
        require_once(__DIR__ . '/../views/Restaurant/detail.php');
    }

    function store()
    {
        $name = isset($_POST['name']) ? $_POST['name'] : '';
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        $image_url = isset($_POST['img_url']) ? $_POST['img_url'] : '';

        // Validate
        $validate = [];
        if ($name == '') {
            $validate['name'] = 'Name is required';
        }
        if ($description == '') {
            $validate['description'] = 'Description is required';
        }
        if ($image_url == '') {
            $validate['img_url'] = 'Image is required';
        }
        if (!empty($validate)) {
            $_SESSION['validate'] = $validate;
            $_SESSION['input'] = $_POST;
            header('Location: ?mod=restaurant&act=add');
            return;
        }

        $input = [
            'name' => $name,
            'description' => $description,
            'img_url' => $image_url,
            'user_id' => $this->user_id
        ];

        $status = $this->model->store($input);
        if ($status == true) {
            $_SESSION['success'] = 'Thêm mới thành công';
            header('Location: ?mod=restaurant&act=list');
        } else {
            $_SESSION['fail'] = 'Thêm mới thất bại';
            header('Location: ?mod=restaurant&act=add');
        }
    }

    function edit()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        $restaurant = $this->model->findById($id);
        $_SESSION['input'] = $restaurant;
        require_once(__DIR__ . '/../views/Restaurant/edit.php');
    }

    function update()
    {
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
        $name = isset($_POST['name']) ? $_POST['name'] : '';
        $description = isset($_POST['description']) ? $_POST['description'] : '';
        $user_id = isset($_POST['user']) ? $_POST['user'] : '';
        $image_url = isset($_POST['img_url']) ? $_POST['img_url'] : '';
        if (!$this->isAuthor($id)) {
            $_SESSION['fail'] = 'Permission denied';
            header('Location: ?mod=restaurant&act=list');
        } else {
            // Validate
            $validate = [];
            if ($name == '') {
                $validate['name'] = 'Name is required';
            }
            if ($description == '') {
                $validate['description'] = 'Description is required';
            }
            if ($image_url == '') {
                $validate['img_url'] = 'Image is required';
            }
            if (!empty($validate)) {
                $_SESSION['validate'] = $validate;
                $_SESSION['input'] = $_POST;
                header('Location: ?mod=restaurant&act=edit&id=' . $id);
                return;
            }

            $input = [
                'name' => $name,
                'description' => $description,
                'img_url' => $image_url,
                'user_id' => $user_id
            ];
            $restaurant = $this->model->edit($id, $input);
            if ($restaurant == true) {
                $_SESSION['success'] = 'Restaurant updated successfully';
                header('Location: ?mod=restaurant&act=list');
            } else {
                $_SESSION['fali'] = 'Restaurant updated failed';
                header('Location: ?mod=restaurant&act=edit&id=' . $id);
            }
        }
    }

    function delete()
    {
        $id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';
        $restaurant = $this->model->findById($id);
        if (!$this->isAuthor($id)) {
            $_SESSION['fail'] = 'Permission denied';
            header('Location: ?mod=restaurant&act=list');
        }
        $status = $this->model->delete($id);
        if ($status == true) {
            $_SESSION['success'] = 'Restaurant deleted successfully';
            header('Location: ?mod=restaurant&act=list');
        } else {
            $_SESSION['fail'] = 'Restaurant deleted failed';
            header('Location: ?mod=restaurant&act=detail&id=' . $id);
        }
    }

    function isAuthor($restaurantId)
    {
        $restaurant = $this->model->findById($restaurantId);
        if ($restaurant['user_id'] == $this->user_id) {
            return true;
        } else {
            return false;
        }
    }

    function add()
    {
        require_once(__DIR__ . '/../views/Restaurant/add.php');
    }
}
