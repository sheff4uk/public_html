<?
$to  = "sheff4uk@gmail.com" ;

$subject = "Заголовок письма";

$message = ' <p>Текст письма</p> </br> <b>1-ая строчка </b> </br><i>2-ая строчка </i> </br>';

$headers  = "Content-type: text/html; charset=UTF-8 \r\n";
$headers .= "From: planner_(KONSTANTA) <planner@konstanta.ltd>\r\n";

mail($to, $subject, $message, $headers);
?>
