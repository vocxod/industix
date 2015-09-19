<?php
session_start();

require 'vendor/autoload.php';
require_once('recaptchalib.php');

$twig_vars = lib\SlimCMS::getTwigVars();
$config = $twig_vars['config'];

// Setup custom Twig view
$twigView = new \Slim\Views\Twig();

$app = new \Slim\Slim(array(
    'debug' => true,
    'view' => $twigView,
    'templates.path' => "themes/". $config["theme"] . "/",
    'twigVars'=> $twig_vars
));

$app->view->parserOptions = array(
    'charset' => 'utf-8',
    'auto_reload' => true,
    'autoescape' => false
);

$app->view->parserExtensions = array(new \Slim\Views\TwigExtension());

$app->notFound(function () use ($app) {
    $twig_vars = lib\SlimCMS::getTwigVars();
    $app->render('404.html.twig', $twig_vars);
});

$authenticate = function ($app) {
    return function () use ($app) {
        if (!isset($_SESSION['user'])) {
            $app->flash('error', 'Login required');
            $app->redirect('/admin');
        }
    };
};

//$paramValue = $app->request->params('paramName');
//var_dump($paramValue); die();

/***********************************************************************************************
* SMS send block 
************************************************************************************************/

// Get a key from https://www.google.com/recaptcha/admin/create
$publickey  = "6LfZSvgSAAAAAA_b9k3cenw8tkiE3LyMrLCvW3HO";
$privatekey = "6LfZSvgSAAAAAJwt41U0q3pO5_tdPHuPy-z6C6iH";

# the response from reCAPTCHA
$resp = null;
# the error code from reCAPTCHA, if any
$error = null;

function sendSms( $sendText, $phone_number='9811714272' ){

    $sSendSms = "http://as135580:559254@gate.prostor-sms.ru/send/?phone=%2B7$phone_number&text=" . urlencode( $sendText );
    
    // echo $sSendSms . "\n";
    $ch = curl_init( $sSendSms );
    curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/5.0"); 
    curl_setopt($ch, CURLOPT_COOKIEJAR,  "smskuka.txt");  
    curl_setopt($ch, CURLOPT_COOKIEFILE, "smskuka.txt");  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    $result = curl_exec($ch); // выполняем запрос curl
    curl_close($ch);
    // write to log

    // send email
    $to = "9852178@gmail.com";
    $subject = "Заказ на сайте";
    $message = $sendText;
    mail ( $to , $subject , $message );
    
    //var_dump($result); die();

    return $result;
}



/***********************************************************************************************************************
 * ADMIN BLOCK
 **********************************************************************************************************************/

// Admin
$app->get('/admin/', function () use ($app) {
    $twig_vars = $app->config('twigVars');
    $app->view->setTemplatesDirectory("admin/");
    $app->render('admin.html.twig', $twig_vars);
});

// Admin Login
$app->post('/admin/login', function () use ($app) {
    $twig_vars = $app->config('twigVars');
    $config = $twig_vars['config'];
    $user = $app->request()->post('user');
    $pass = sha1($app->request()->post('password'));

    if ($config['user'] == $user && $config['password'] == $pass) {
        $_SESSION['user'] = $user;
        $_SESSION['pass'] = $pass;
        $app->redirect($config['url'].'/admin/pages');
    } else {
        $app->redirect($config['url'].'/admin');
    }
});

// Admin Logout
$app->get('/admin/logout', function () use ($app) {
    $twig_vars = $app->config('twigVars');
    $config = $twig_vars['config'];
    unset($_SESSION['user']);
    unset($_SESSION['pass']);
    $app->redirect($config['url'].'/admin');
});

// Admin Page
$app->get('/admin/pages', $authenticate($app), function () use ($app) {
    $twig_vars = $app->config('twigVars');
    $templates = lib\SlimCMS::getTemplates($app->config('templates.path'));
    $twig_vars['templates'] = $templates;
    $app->view->setTemplatesDirectory("admin/");
    $app->render('pages.html.twig', $twig_vars);
});

// Admin Page Id
$app->get('/admin/pages/:slug', function ($slug) use ($app) {
    $twig_vars = $app->config('twigVars');
    $pages = $twig_vars['pages'];
    $twig_vars['templates'] = lib\SlimCMS::getTemplates($app->config('templates.path'));
    $twig_vars['page'] = $pages[$slug];
    if (isset($pages[$slug])) {
        $app->view->setTemplatesDirectory("admin/");
        $app->render('pages.html.twig', $twig_vars);
    } else {
        $app->notFound();
    }
});

// Admin Blog
$app->get('/admin/blog', $authenticate($app), function () use ($app) {
    $twig_vars = $app->config('twigVars');
    $app->view->setTemplatesDirectory("admin/");
    $app->render('blog.html.twig', $twig_vars);
});

// Admin Blog Id
$app->get('/admin/blog/:slug', function ($slug) use ($app) {
    $twig_vars = $app->config('twigVars');
    $posts = $twig_vars['blog'];
    $twig_vars['post'] = $posts[$slug];
    if (isset($posts[$slug])) {
        $app->view->setTemplatesDirectory("admin/");
        $app->render('blog.html.twig', $twig_vars);
    } else {
        $app->notFound();
    }
});

// Admin Menus
$app->get('/admin/menus', $authenticate($app), function () use ($app) {
    $twig_vars = $app->config('twigVars');
    $app->view->setTemplatesDirectory("admin/");
    $app->render('menus.html.twig', $twig_vars);
});

// Admin Menu Slug
$app->get('/admin/menus/:slug', function ($slug) use ($app) {
    $twig_vars = $app->config('twigVars');
    $menus = $twig_vars['menus'];
    $twig_vars['menu'] = $menus[$slug];
    $twig_vars['menuName'] = $slug;
    if (isset($menus[$slug])) {
        $app->view->setTemplatesDirectory("admin/");
        $app->render('menus.html.twig', $twig_vars);
    } else {
        $app->notFound();
    }
});

// Admin Config
$app->get('/admin/config', $authenticate($app), function () use ($app) {
    $twig_vars = $app->config('twigVars');
    $app->view->setTemplatesDirectory("admin/");
    $app->render('config.html.twig', $twig_vars);
});

// Admin Media
$app->get('/admin/media', $authenticate($app), function () use ($app) {
    $twig_vars = $app->config('twigVars');
    $config = $twig_vars['config'];
    $media = lib\SlimCMS::getMedia($config['url']);
    $twig_vars['media'] = $media;

    $app->view->setTemplatesDirectory("admin/");
    $app->render('media.html.twig', $twig_vars);
});

// POST Media
$app->post('/admin/media', function () use ($app) {
    $twig_vars = $app->config('twigVars');
    $config = $twig_vars['config'];
    $allowedExts = array("gif", "jpeg", "jpg", "png", "JPG");
    $temp = explode(".", $_FILES["file"]["name"]);
    $extension = end($temp);
    if ((($_FILES["file"]["type"] == "image/gif")
            || ($_FILES["file"]["type"] == "image/jpeg")
            || ($_FILES["file"]["type"] == "image/jpg")
            || ($_FILES["file"]["type"] == "image/pjpeg")
            || ($_FILES["file"]["type"] == "image/x-png")
            || ($_FILES["file"]["type"] == "image/png"))
        && ($_FILES["file"]["size"] < 2000000)
        && in_array($extension, $allowedExts))
    {
        if ($_FILES["file"]["error"] > 0) {
            echo "Return Code: " . $_FILES["file"]["error"] . "<br>";
        } else {
            echo "Upload: " . $_FILES["file"]["name"] . "<br>";
            echo "Type: " . $_FILES["file"]["type"] . "<br>";
            echo "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
            echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br>";

            if (file_exists($config['path']."/content/media/" . $_FILES["file"]["name"])) {
                echo $_FILES["file"]["name"] . " already exists. ";
            } else {
                move_uploaded_file($_FILES["file"]["tmp_name"],
                                   $config['path']."/content/media/" . $_FILES["file"]["name"]);
                echo "Stored in: " . "/content/media/" . $_FILES["file"]["name"];
                $app->redirect($config['url'].'/admin/media');
            }
        }
    } else {
        echo "Invalid file";
    }
});

/***********************************************************************************************************************
 * API BLOCK
 **********************************************************************************************************************/

// GET Pages
$app->get('/api/page', function () use ($app) {
    $pages = lib\SlimCMS::getAllFrom('pages');
    echo json_encode($pages);
});

// POST Page
$app->post('/api/page', function () use ($app) {
    $page = $app->request()->post();
    lib\SlimCMS::saveJsonTo($page, "content/pages/");
});

// DELETE Page
$app->delete('/api/page/:slug', function ($slug) use ($app) {
    lib\SlimCMS::delJson($slug, "content/pages/");
});

// POST Blog
$app->post('/api/blog', function () use ($app) {
    $post = $app->request()->post();
    $post['DateTime'] = time();
    lib\SlimCMS::saveJsonTo($post, "content/blog/");
});

// DELETE Blog
$app->delete('/api/blog/:slug', function ($slug) use ($app) {
    lib\SlimCMS::delJson($slug, "content/blog/");
});

// POST Menu
$app->post('/api/menus', function () use ($app) {
    $menus = $app->request()->post();
    lib\SlimCMS::saveJsonToMenu($menus['Variables'], $menus['Name'], "content/menu/");
});

// DELETE Menu
$app->delete('/api/menus/:slug', function ($slug) use ($app) {
    lib\SlimCMS::delJson($slug, "content/menu/");
});

// POST Config
$app->post('/api/config', function () use ($app) {
    $twig_vars = $app->config('twigVars');
    $config = $twig_vars['config'];
    $post = $app->request()->post();
    if($post['password'] == "")
        $post['password'] = $config['password'];
    else
        $post['password'] = sha1($post['password']);
    lib\SlimCMS::saveJsonTo($post, "content/");
});

// GET Media
$app->get('/api/media', function () use ($app) {
    $twig_vars = $app->config('twigVars');
    $config = $twig_vars['config'];
    $media = lib\SlimCMS::getMedia($config['url']);
    echo json_encode($media);
});

// DELETE Media
$app->delete('/api/media/:img', function ($img) use ($app) {
    lib\SlimCMS::delFile($img, "content/media/");
});

/***********************************************************************************************************************
 * PUBLIC BLOCK
 **********************************************************************************************************************/

// Index Page
$app->get('/', function () use ($app) {
    $twig_vars = $app->config('twigVars');
    $pages = $twig_vars['pages'];
    $twig_vars['page'] = $pages['index'];
    if ($twig_vars['page'] != 0) {
        $app->render('index.html.twig', $twig_vars);
    }
});
$app->get('/spb', function () use ($app) {
    $twig_vars = $app->config('twigVars');
    $pages = $twig_vars['pages'];
    $twig_vars['page'] = $pages['index'];
    if ($twig_vars['page'] != 0) {
        $app->render('index.html.twig', $twig_vars);
    }
});

// Blog
$app->get('/blog/', function () use ($app) {
    $twig_vars = $app->config('twigVars');
    $app->render("blog.html.twig", $twig_vars);
});

// Blog Article
$app->get('/blog/:slug', function ($slug) use ($app) {
    $twig_vars = $app->config('twigVars');
    $blog = $twig_vars['blog'];
    $twig_vars['page'] = $blog[$slug];
    if (isset($blog[$slug]))
        $app->render("article.html.twig", $twig_vars);
    else
        $app->notFound();
});

// Page: HASBEND per HOUR
$app->get('/:city/master/home', function ($slug) use ($app) {
    $twig_vars = $app->config('twigVars');
    //var_dump($slug);
    //var_dump($twig_vars); die();
    $slug = "masterhomespb";
    $config = $twig_vars['config'];
    if ($slug == "index")
        $app->redirect($config['url']);

    if (isset($twig_vars['pages'][$slug]))
        $page = $twig_vars['pages'][$slug];
    else
        $app->notFound();

    if (isset($page)) {
        $twig_vars['page'] = $page;
        (isset($page['Template']) && $page['Template']!="") ? $template = "templates/".$page['Template']
            : $template = "page.html.twig";
        if (file_exists ($app->config('templates.path').$template))
            $app->render($template, $twig_vars);
        else
            echo "The template: ".$template. " doen't exist into the theme: ". $config['theme'];
    } else {
        $app->notFound();
    }
});

// Page: QUICK ORDER
// TODO
$app->get('/:city/quick/order', function ($slug) use ($app) {
    //var_dump( $)
    $twig_vars = $app->config('twigVars');
    $slug = "quickorderspb";
    $config = $twig_vars['config'];
    if ($slug == "index")
        $app->redirect($config['url']);

    if (isset($twig_vars['pages'][$slug]))
        $page = $twig_vars['pages'][$slug];
    else
        $app->notFound();

    if (isset($page)) {
        $twig_vars['page'] = $page;
        (isset($page['Template']) && $page['Template']!="") ? $template = "templates/".$page['Template']
            : $template = "page.html.twig";
        if (file_exists ($app->config('templates.path').$template))
            $app->render($template, $twig_vars);
        else
            echo "The template: ".$template. " doen't exist into the theme: ". $config['theme'];
    } else {
        $app->notFound();
    }
});

$app->post('/:city/quick/order', function ($slug) use ($app) {
    $twig_vars = $app->config('twigVars');
    // Register API keys at https://www.google.com/recaptcha/admin
    $siteKey = "6LciSPgSAAAAAHbaXV3bUBPZ3v7vVwxU1HMt0sFI";
    $secret = "6LciSPgSAAAAALnwh6msu0EL5ADuGMWbVAK09KM-";
    // reCAPTCHA supported 40+ languages listed here: https://developers.google.com/recaptcha/docs/language
    $lang = "ru";
    // The response from reCAPTCHA
    $resp = null;
    // The error code from reCAPTCHA, if any
    $error = null;
    $reCaptcha = new ReCaptcha($secret);
    // Was there a reCAPTCHA response?
    if ($app->request->params("g-recaptcha-response") ) {
        
        $resp = $reCaptcha->verifyResponse(
            $_SERVER["REMOTE_ADDR"],
            $app->request->params("g-recaptcha-response")
        );

        if ($resp != null && $resp->success) {
            $twig_vars['order_success'] = "1";
            // @todo
            //Send SMS to our phone(s)
            // var_dump('send SMS'); 
            // redirect to /:city/order/success page
            $smsText = "Заказ. Тел:" . $_POST['customer_phone'] . " Имя:" . $_POST['customer_name'] . " Aдрес:" . $_POST['customer_address']  . " Работа:" . substr( trim($_POST['customer_job']), 0, 128) ;
            $result = sendSms( $smsText, "9626852178" );
            //$result = sendSms( $smsText, "9811714272" );    
            $app->redirect('/spb/order/success');
        } else {
            $twig_vars['order_success'] = '2'; // captcha not valid
        }
    }
    // end GOOGLE CAPTCHA
    $slug = "quickorderspb";
    $config = $twig_vars['config'];
    if ($slug == "index")
        $app->redirect($config['url']);

    if (isset($twig_vars['pages'][$slug]))
        $page = $twig_vars['pages'][$slug];
    else
        $app->notFound();

    if (isset($page)) {
        $twig_vars['page'] = $page;
        (isset($page['Template']) && $page['Template']!="") ? $template = "templates/".$page['Template']
            : $template = "page.html.twig";
        if (file_exists ($app->config('templates.path').$template))
            $app->render($template, $twig_vars);
        else
            echo "The template: ".$template. " doen't exist into the theme: ". $config['theme'];
    } else {
        $app->notFound();
    }
});

// Page: QUICK ORDER RESULT
// TODO
$app->get('/:city/order/success', function ($slug) use ($app) {
    $twig_vars = $app->config('twigVars');
    $slug = "quickorderresult";
    $config = $twig_vars['config'];
    if ($slug == "index")
        $app->redirect($config['url']);

    if (isset($twig_vars['pages'][$slug]))
        $page = $twig_vars['pages'][$slug];
    else
        $app->notFound();

    if (isset($page)) {
        $twig_vars['page'] = $page;
        (isset($page['Template']) && $page['Template']!="") ? $template = "templates/".$page['Template']
            : $template = "page.html.twig";
        if (file_exists ($app->config('templates.path').$template))
            $app->render($template, $twig_vars);
        else
            echo "The template: ".$template. " doen't exist into the theme: ". $config['theme'];
    } else {
        $app->notFound();
    }
});

// Page: MASTER for job with WATER EQUIPMENT in home
$app->get('/:city/master/water', function ($slug) use ($app) {
    $twig_vars = $app->config('twigVars');
    //var_dump($slug);
    //var_dump($twig_vars); die();
    $slug = "masterwaterspb";
    $config = $twig_vars['config'];
    if ($slug == "index")
        $app->redirect($config['url']);

    if (isset($twig_vars['pages'][$slug]))
        $page = $twig_vars['pages'][$slug];
    else
        $app->notFound();

    if (isset($page)) {
        $twig_vars['page'] = $page;
        (isset($page['Template']) && $page['Template']!="") ? $template = "templates/".$page['Template']
            : $template = "page.html.twig";
        if (file_exists ($app->config('templates.path').$template))
            $app->render($template, $twig_vars);
        else
            echo "The template: ".$template. " doen't exist into the theme: ". $config['theme'];
    } else {
        $app->notFound();
    }
});

// Page: MASTER for job with ELECTRO EQUIPMENT in home
$app->get('/:city/master/electro', function ($slug) use ($app) {
    $twig_vars = $app->config('twigVars');
    //var_dump($slug);
    //var_dump($twig_vars); die();
    $slug = "masterelectrospb";
    $config = $twig_vars['config'];
    if ($slug == "index")
        $app->redirect($config['url']);

    if (isset($twig_vars['pages'][$slug]))
        $page = $twig_vars['pages'][$slug];
    else
        $app->notFound();

    if (isset($page)) {
        $twig_vars['page'] = $page;
        (isset($page['Template']) && $page['Template']!="") ? $template = "templates/".$page['Template']
            : $template = "page.html.twig";
        if (file_exists ($app->config('templates.path').$template))
            $app->render($template, $twig_vars);
        else
            echo "The template: ".$template. " doen't exist into the theme: ". $config['theme'];
    } else {
        $app->notFound();
    }
});

// Page: MASTER for job with ELECTRO FURNITURE in home
$app->get('/:city/master/furniture', function ($slug) use ($app) {
    $twig_vars = $app->config('twigVars');
    //var_dump($slug);
    //var_dump($twig_vars); die();
    $slug = "masterfurniturespb";
    $config = $twig_vars['config'];
    if ($slug == "index")
        $app->redirect($config['url']);

    if (isset($twig_vars['pages'][$slug]))
        $page = $twig_vars['pages'][$slug];
    else
        $app->notFound();

    if (isset($page)) {
        $twig_vars['page'] = $page;
        (isset($page['Template']) && $page['Template']!="") ? $template = "templates/".$page['Template']
            : $template = "page.html.twig";
        if (file_exists ($app->config('templates.path').$template))
            $app->render($template, $twig_vars);
        else
            echo "The template: ".$template. " doen't exist into the theme: ". $config['theme'];
    } else {
        $app->notFound();
    }
});

// Page: FAQ
$app->get('/:city/master/faq', function ($slug) use ($app) {
    $twig_vars = $app->config('twigVars');
    //var_dump($slug);
    //var_dump($twig_vars); die();
    $slug = "faq";
    $config = $twig_vars['config'];
    if ($slug == "index")
        $app->redirect($config['url']);

    if (isset($twig_vars['pages'][$slug]))
        $page = $twig_vars['pages'][$slug];
    else
        $app->notFound();

    if (isset($page)) {
        $twig_vars['page'] = $page;
        (isset($page['Template']) && $page['Template']!="") ? $template = "templates/".$page['Template']
            : $template = "page.html.twig";
        if (file_exists ($app->config('templates.path').$template))
            $app->render($template, $twig_vars);
        else
            echo "The template: ".$template. " doen't exist into the theme: ". $config['theme'];
    } else {
        $app->notFound();
    }
});

// Page
$app->get('/:slug', function ($slug) use ($app) {
    $twig_vars = $app->config('twigVars');
    $config = $twig_vars['config'];
    if ($slug == "index")
        $app->redirect($config['url']);

    if (isset($twig_vars['pages'][$slug]))
        $page = $twig_vars['pages'][$slug];
    else
        $app->notFound();

    if (isset($page)) {
        $twig_vars['page'] = $page;
        (isset($page['Template']) && $page['Template']!="") ? $template = "templates/".$page['Template']
            : $template = "page.html.twig";
        if (file_exists ($app->config('templates.path').$template))
            $app->render($template, $twig_vars);
        else
            echo "The template: ".$template. " doen't exist into the theme: ". $config['theme'];
    } else {
        $app->notFound();
    }
});


$app->run();