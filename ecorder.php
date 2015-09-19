<!DOCTYPE html>
<html>
<meta charset="utf-8">
  <body>
    <form action="" method="post">
<?php

require_once('recaptchalib.php');


function sendSms( $sendText, $phone_number='9811714272' ){
    $sSendSms = "http://t89811716286:182834@gate.prostor-sms.ru/send/?phone=%2B7$phone_number&sender=LOYALSYSTEM&text=" . urlencode( $sendText );
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
    $to = "zaebok88@gmail.com, zx1991ru@gmail.com";
    $subject = "Заказ на сайте";
    $message = $sendText;
    mail ( $to , $subject , $message );
    
    return $result;
}


// Get a key from https://www.google.com/recaptcha/admin/create
$publickey  = "6LfZSvgSAAAAAA_b9k3cenw8tkiE3LyMrLCvW3HO";
$privatekey = "6LfZSvgSAAAAAJwt41U0q3pO5_tdPHuPy-z6C6iH";

# the response from reCAPTCHA
$resp = null;
# the error code from reCAPTCHA, if any
$error = null;

?>
    
        
        <div>
            <table width="75%" align="center">
                <tr>
                    <td colspan="2">
                    <?php
                    # was there a reCAPTCHA response?
                    if ($_POST["recaptcha_response_field"]) {
                            $resp = recaptcha_check_answer ($privatekey,
                                $_SERVER["REMOTE_ADDR"],
                                $_POST["recaptcha_challenge_field"],
                                $_POST["recaptcha_response_field"]);

                            if ($resp->is_valid) {
                                //echo "You got it!";
                                /*
                                Отправляем SMS через оператора
                                */
                                echo "<span style='color:green;'>Спасибо, Ваш заказ принят. Наши сотрудники связутся с Вами в ближайшее время</span>";
                                /*
                                sleep( 5 );
                                header( "Location: index.php" );
                                */
                                //  t89811716286 Пароль: 182834
                                // http://api_login:api_password@gate.prostor-sms.ru/send/?phone=%2B71234567890&text=test 
                                // REST 
                                // http://api.prostor-sms.ru/messages/v2/send/?phone=%2B71234567890&text=test                       
                                // Ответ: A132571BC=accepted 
                                // если ответ иной - сообщить в окне про необходимость дозвона ручками
                                // @todo хранить заказы в БД
                                echo "<p>";
                                $orderNumber = mt_rand( 1000, 9999 );
                                echo "Номер заказа: $orderNumber <br/>"  ;
                                echo "Заказчик: " . $_POST['customer_name'] . "<br/>";
                                echo "Телефон для связи: " . $_POST['customer_phone'] . "<br/>";
                                echo "Адрес заказа: " . $_POST['customer_address'] . "<br/>";
                                echo "Работы: " . $_POST['customer_job'] . "<br/>";
                                echo "</p>";

                                $smsText = "Заказ. Тел:" . $_POST['customer_phone'] . " Имя:" . $_POST['customer_name'] . " Aдрес:" . $_POST['customer_address']  . " Работа:" . substr( $_POST['customer_job'], 0, 128) ;

                                //echo "<span style='color:green;'>$smsText</span><br/>";
                                //$result = sendSms( $smsText, "9811714272" );
                                $result = sendSms( $smsText, "9626852178" );
                                echo "<p>$result</p>";


                                die();    
                            } else {
                                    # set the error code so that we can display it
                                    echo "<span style='color:red;'>Не верно набранные символы!</span>";
                                    $error = $resp->error;
                            }
                    }
                    ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <h3>Сделать ЗАКАЗ за 48 часов: <span style="color:red;">СКИДКА 10%</span></h3>                
                    </td>
                </tr>
                <tr>
                    <td>Имя</td>
                    <td>
                        <input type="text" name="customer_name" required placeholder="Ваше имя" value="<?php echo $_POST['customer_name'] ?>" ><br/>
                    </td>
                </tr>
                <tr>
                    <td>Адрес</td>
                    <td>
                        <input type="text" name="customer_address" required placeholder="Адрес" value="<?php echo $_POST['customer_address'] ?>" >
                    </td>
                </tr>
                <tr>
                    <td>Телефон</td>
                    <td>
                        <input type="text" name="customer_phone" required placeholder="Номер вашего телефона" value="<?php echo $_POST['customer_phone'] ?>" >
                    </td>
                </tr>
                <tr>
                    <td>Описание работ</td>
                    <td>
                        <textarea name="customer_job" id="customer_job" cols="40" rows="7">
                            <?php echo $_POST['customer_job'] ?>
                        </textarea>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <?php 
                            echo recaptcha_get_html($publickey, $error);
                        ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="submit" value="Сделать заказ" style="width:240px; height:40px; " />
                    </td>
                </tr>
            </table>
        </div>
    </form>
  </body>
</html>
