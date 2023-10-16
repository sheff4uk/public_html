<?
include "../config.php";
?>

<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<script src="../js/jquery-1.11.3.min.js"></script>
	<script src="https://kit.fontawesome.com/020f21ae61.js" crossorigin="anonymous"></script>

<?
$PS_ID = $_GET["PS_ID"];
echo "<title>Накладная №{$PS_ID}</title>";
?>
	<style>
		@media print {
			@page {
				size: portrait;
			}
		}

		body, td {
			font-family: Trebuchet MS, Tahoma, Verdana, Arial, sans-serif;
			font-size: 10pt;
		}
		table {
			width: 100%;
			border-collapse: collapse;
			border-spacing: 0px;
		}
		.thead {
			text-align: center;
			font-weight: bold;
		}
		td, th {
			padding: 3px;
			border: 1px solid black;
			line-height: 1em;
		}
		.nowrap {
			white-space: nowrap;
		}
        .blank {
            /* width: 47%; */
        }
        .page {
            /* display: flex;
            justify-content: space-between;
            flex-wrap: wrap; */
        }
        /* @media print {
            .page {
                page-break-after: always;
            } 
        }  */
	</style>
</head>
<body>

<?
    // Получаем дату планируемой отгрузки
    $query = "
        SELECT DATE_FORMAT(PS.ps_date, '%d.%m.%Y') ps_date_format
            ,PS.priority
        FROM plan__Shipment PS
        WHERE PS.PS_ID = {$PS_ID}
    ";
    $res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
    $ps_date_format = $row["ps_date_format"];
    $priority = $row["priority"];

    // Цикл по списку грузоотправителей
    $query = "
        SELECT M.M_ID
            ,M.company
        FROM plan__ShipmentCWP PSC
        JOIN CounterWeightPallet CWP ON CWP.CWP_ID = PSC.CWP_ID
        JOIN Manufacturer M ON M.M_ID = CWP.M_ID
        WHERE PSC.PS_ID = {$PS_ID}
        GROUP BY M.M_ID
    ";
    $res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
    while( $row = mysqli_fetch_array($res) ) {
        $M_company = $row["company"];
        $M_ID = $row["M_ID"];

        // Узнаем грузополучателя
        $query = "
            SELECT CB.company
            FROM plan__ShipmentCWP PSC
            JOIN CounterWeightPallet CWP ON CWP.CWP_ID = PSC.CWP_ID
                AND CWP.M_ID = {$M_ID}
            JOIN ClientBrand CB ON CB.CB_ID = CWP.CB_ID
            WHERE PSC.PS_ID = {$PS_ID}
            LIMIT 1
        ";
        $subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
        $subrow = mysqli_fetch_array($subres);
        $CB_company = $subrow["company"];
 
        // Статические данные
        $statics = "
            <p style='font-size: 1.1em; float: left; margin: 0px;'>Грузоотправитель: <span style='text-decoration: underline;'>{$M_company}</span></p>\n
            <p style='text-align: right;'>от {$ps_date_format} г.</p>\n
            <p style='text-align: center; font-size: 1.3em;'><b style='text-decoration: underline;'>Накладная №{$PS_ID} / {$priority}</b></p>\n
            <!--<p style='font-size: 1.1em;'>Грузоотправитель: <span style='text-decoration: underline;'>{$M_company}</span></p>\n-->
            <table>\n
                <thead>\n
                    <tr>\n
                        <th>№ п-п</th>\n
                        <th>Наименование</th>\n
                        <th>Ед. изм.</th>\n
                        <th>Кол-во</th>\n
                        <th>Ед. изм.</th>\n
                        <th>Кол-во</th>\n
                    </tr>\n
                </thead>\n
                <tbody>\n
        ";

        $query = "
            SELECT IFNULL(CW.drawing_item, CWP.cwp_name) item
                ,PSC.quantity
                ,PSC.quantity * CWP.in_pallet amount
            FROM plan__ShipmentCWP PSC
            JOIN CounterWeightPallet CWP ON CWP.CWP_ID = PSC.CWP_ID
                AND CWP.M_ID = {$M_ID}
            LEFT JOIN CounterWeight CW ON CW.CW_ID = CWP.CW_ID
            WHERE PSC.PS_ID = {$PS_ID}
        ";
        $subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
        $i = 1;
        while( $subrow = mysqli_fetch_array($subres) ) {
            $statics .= "
                <tr>
                    <td>{$i}</td>\n
                    <td>{$subrow["item"]}</td>\n
                    <td>паллет</td>\n
                    <td>{$subrow["quantity"]}</td>\n
                    <td>шт.</td>\n
                    <td>{$subrow["amount"]}</td>\n
                </tr>\n
            ";
            $i++;
        }

        // Дополнение пустыми строками  
        for( $i; $i<=8; $i++ ) {
            $statics .= "
                <tr>
                    <td>{$i}</td>\n
                    <td></td>\n
                    <td></td>\n
                    <td></td>\n
                    <td></td>\n
                    <td></td>\n
                </tr>\n
            ";
        }
    
        $statics .= "
                </tbody>\n
            </table>\n
            <div style='margin-top: 20px; display: flex; justify-content: space-between; flex-wrap: wrap;'>\n
                <div style='width: 47%;'>\n
                    <p style='margin-bottom: 0px;'>Сдал: _______________ / _______________</p>\n
                    <p style='margin-top: 0px;'><sup style='margin-left: 75px;'>подпись</sup><sup style='margin-left: 85px;'>Ф.И.О.</sup></p>\n
                </div>\n
                <div style='width: 47%;'>\n
                    <p style='margin-bottom: 0px;'>Принял: _______________ / _______________</p>\n
                    <p style='margin-top: 0px;'><sup style='margin-left: 90px;'>подпись</sup><sup style='margin-left: 85px;'>Ф.И.О.</sup></p>\n
                </div>\n
            </div>\n
        ";

        // Формируем бланк
        $blank = "
            <div style='border-bottom: 1px dotted; position: relative; height: 333px;'>\n
                <div style='position: absolute; top: 20px; border: 2px solid; width: 200px; height: 33px; text-align: center; font-weight: bold;'>ЭКЗЕМПЛЯР<br>охраны</div>\n
                {$statics}
            </div>\n
            <div style='border-bottom: 1px dotted; position: relative; height: 333px;'>\n
                <div style='position: absolute; top: 20px; border: 2px solid; width: 200px; height: 33px; text-align: center; font-weight: bold;'>ЭКЗЕМПЛЯР<br>{$CB_company}</div>\n
                {$statics}
            </div>\n
            <div style='position: relative; height: 333px;'>\n
                <div style='position: absolute; top: 20px; border: 2px solid; width: 150px; height: 33px; text-align: center; font-weight: bold;'>ЭКЗЕМПЛЯР<br>водителя</div>\n
                {$statics}
            </div>\n
        ";
        
        echo $blank;
    }
?>
</html>
