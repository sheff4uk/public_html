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
				size: landscape;
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
            width: 47%;
        }
        .page {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        @media print {
            .page {
                page-break-after: always;
            } 
        } 
	</style>
</head>
<body>

<?
    // Получаем дату планируемой отгрузки
    $query = "
        SELECT DATE_FORMAT(PS.ps_date, '%d.%m.%Y') ps_date_format
        FROM plan__Shipment PS
        WHERE PS.PS_ID = {$PS_ID}
    ";
    $res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
	$row = mysqli_fetch_array($res);
    $ps_date_format = $row["ps_date_format"];

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
    
    
        $blank = "
            <div class='blank'>\n
                <p style='text-align: right;'>от {$ps_date_format} г.</p>\n
                <p style='text-align: center; font-size: 1.3em;'>Накладная №{$PS_ID}</p>\n
                <p style='font-size: 1.1em;'>Грузоотправитель: <span style='text-decoration: underline;'>{$M_company}</span></p>\n
                <p style='font-size: 1.1em;'>Грузополучатель: <span style='text-decoration: underline;'>{$CB_company}</span></p>\n
                <table>\n
                    <thead>\n
                        <tr>\n
                            <th>№ п-п</th>\n
                            <th>Наименование</th>\n
                            <th>Ед. изм.</th>\n
                            <th>Кол-во</th>\n
                        </tr>\n
                    </thead>\n
                    <tbody>\n
        ";
    
        $query = "
            SELECT IFNULL(CW.drawing_item, CWP.cwp_name) item
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
            $blank .= "
                <tr>
                    <td>{$i}</td>\n
                    <td>{$subrow["item"]}</td>\n
                    <td>шт.</td>\n
                    <td>{$subrow["amount"]}</td>\n
                </tr>\n
            ";
            $i++;
        }
    
        $blank .= "
                    </tbody>\n
                </table>\n
                <div class='page'>\n
                    <div class='blank'>\n
                        <p style='margin-bottom: 0px;'>Сдал: _________ / ____________</p>\n
                        <p style='margin-top: 0px;'><sup style='margin-left: 55px;'>подпись</sup><sup style='margin-left: 55px;'>Ф.И.О.</sup></p>\n
                    </div>\n
                    <div class='blank'>\n
                        <p style='margin-bottom: 0px;'>Принял: _________ / ____________</p>\n
                        <p style='margin-top: 0px;'><sup style='margin-left: 70px;'>подпись</sup><sup style='margin-left: 55px;'>Ф.И.О.</sup></p>\n
                    </div>\n
                </div>\n
            </div>\n
        ";
    
        echo "<div class='page'>\n";
        echo $blank;
        echo $blank;
        echo "</div>\n";
    }
?>
</html>
