
<?php 


if (!empty($_GET['picture']) && !empty($_GET['size'])) {
    ob_end_clean();
    require __DIR__.'/custom-table/vendor/autoload.php';

    switch ($_GET['size']) {
        case 'xs':
            $tableSize = [18, 13];
            break;
        case 's':
            $tableSize = [30, 21];
            break;
        case 'm':
            $tableSize = [45, 30];
            break;
        case 'l':
            $tableSize = [60, 40];
            break;
        case 'xl':
            $tableSize = [80, 60];
            break;
        case 'xxl':
            $tableSize = [100, 70];
            break;
        default:
            $tableSize = [100, 70];
            break;
    }

    list($width, $height) = $tableSize;
    // create new PDF document
    $orientation = $_GET['position'] == "portrait" ? "P" : "L";
    $pdf = new TCPDF($orientation, "cm", array($width+40, $height+40), true, 'UTF-8', false, true, true);


    // Définir les marges
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

    $pdf->SetFillColor(100, 50, 0, 0);  // Magenta
    $pdf->SetTextColor(0, 100, 0, 0); 
    // Ajouter une nouvelle page
    $pdf->AddPage();
    
    // Définir le chemin vers l'image
    $image_file = $_GET["picture"];

    // Récupérer les dimensions de l'image
    $image_size = getimagesize($image_file);

    // Calculer les ratios de l'image
    $ratio_w = $width / $image_size[0];
    $ratio_h = $height / $image_size[1];

    // Calculer le ratio de l'image qui convient le mieux au cadre
    $ratio = min($ratio_w, $ratio_h);

    // Calculer les nouvelles dimensions de l'image
    $new_width = $image_size[0] * $ratio;
    $new_height = $image_size[1] * $ratio;

    // Ajuster l'image en couverture dans un cadre de 100cm / 70cm
    $bwidth = $width;
    $width = $_GET['position'] == "portrait" ? $height : $width;
    $height = $_GET['position'] == "portrait" ? $bwidth : $height;
    $pdf->Image($image_file, 20, 20, $width, $height, '', '', '', false, 300, '', false, false, 0);
    //Close and output PDF document

    $pdf->Output();
}
//============================================================+
// END OF FILE
//============================================================+
