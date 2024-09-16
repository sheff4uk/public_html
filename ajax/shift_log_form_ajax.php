<?php
include_once "../checkrights.php";

$F_ID = $_GET["F_ID"];
$day = $_GET["day"];
$week = $_GET["week"];

$html = "
    <input type='hidden' name='F_ID' value='{$F_ID}'>
    <input type='hidden' name='day' value='{$day}'>
    <input type='hidden' name='week' value='{$week}'>
    <table style='width: 100%; table-layout: fixed;'>
    <thead>
        <tr>
            <th>День</th>
            <th>Смена</th>
            <th>Мастер</th>
            <th>Оператор</th>
        </tr>
    </thead>
    <tbody style='text-align: center;'>
";

$query = "
    SELECT WS.shift_num
        ,SL.master
        ,SL.operator
    FROM WorkingShift WS
    LEFT JOIN ShiftLog SL ON SL.F_ID = WS.F_ID
        AND SL.shift = WS.shift_num
        AND SL.working_day LIKE STR_TO_DATE('{$day}', '%d.%m.%Y')
    WHERE STR_TO_DATE('{$day}', '%d.%m.%Y') BETWEEN WS.valid_from AND IFNULL(WS.valid_to, CURDATE())
        AND WS.F_ID = {$F_ID}
    ORDER BY shift_num
";
$res = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
while ($row = mysqli_fetch_array($res)) {
    $html .= "
        <tr>
            <td>{$day}</td>
            <td><h1>{$row["shift_num"]}</h1></td>
            <td>
                <select name='master[{$row["shift_num"]}]'>
                    <option value=''></option>
    ";
    
    $query = "
        SELECT USR_ID
            ,USR_Name(USR_ID) name
        FROM Users
        WHERE F_ID = {$F_ID}
            AND RL_ID = 2
            AND act = 1
        ORDER BY USR_ID
    ";
    $subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
    while( $subrow = mysqli_fetch_array($subres) ) {
        $selected = ($subrow["USR_ID"] == $row["master"]) ? "selected" : "";
        $html .= "<option value='{$subrow["USR_ID"]}' {$selected}>{$subrow["name"]}</option>";
    }
    
    $html .= "
        <optgroup label='уволены:'>
    ";
    
    $query = "
        SELECT USR_ID
            ,USR_Name(USR_ID) name
        FROM Users
        WHERE F_ID = {$F_ID}
            AND RL_ID = 2
            AND act = 0
        ORDER BY USR_ID
    ";
    $subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
    while( $subrow = mysqli_fetch_array($subres) ) {
        $selected = ($subrow["USR_ID"] == $row["master"]) ? "selected" : "";
        $html .= "<option value='{$subrow["USR_ID"]}' {$selected}>{$subrow["name"]}</option>";
    }
    
    $html .= "
                </optgroup>
            </select>
        </td>
        <td>
            <select name='operator[{$row["shift_num"]}]'>
                <option value=''></option>
    ";
    
    $query = "
        SELECT USR_ID
            ,USR_Name(USR_ID) name
        FROM Users
        WHERE F_ID = {$F_ID}
            AND RL_ID = 3
            AND act = 1
        ORDER BY USR_ID
    ";
    $subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
    while( $subrow = mysqli_fetch_array($subres) ) {
        $selected = ($subrow["USR_ID"] == $row["operator"]) ? "selected" : "";
        $html .= "<option value='{$subrow["USR_ID"]}' {$selected}>{$subrow["name"]}</option>";
    }
    
    $html .= "
        <optgroup label='уволены:'>\n
    ";
    
    $query = "
        SELECT USR_ID
            ,USR_Name(USR_ID) name
        FROM Users
        WHERE F_ID = {$F_ID}
            AND RL_ID = 3
            AND act = 0
        ORDER BY USR_ID
    ";
    $subres = mysqli_query( $mysqli, $query ) or die("Invalid query: " .mysqli_error( $mysqli ));
    while( $subrow = mysqli_fetch_array($subres) ) {
        $selected = ($subrow["USR_ID"] == $row["operator"]) ? "selected" : "";
        $html .= "<option value='{$subrow["USR_ID"]}' {$selected}>{$subrow["name"]}</option>";
    }
    
    $html .= "
                    </optgroup>
                </select>
            </td>
        </tr>
    ";
}

$html .= "
        </tbody>
    </table>
";

$html = str_replace("\n", "", addslashes($html));
echo "$('#shift_log_form fieldset').html('{$html}');";
?>