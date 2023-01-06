<?php

require_once "personnalized.php";

function my_script()
{
    wp_enqueue_style('personalized-style', get_theme_file_uri() . "/custom-table/assets/css/tabloide-personalized-style.css");
    wp_enqueue_style('personalized-responsive-style', get_theme_file_uri() . "/custom-table/assets/css/personalized-responsive.css");
    wp_enqueue_script('table-personnalize-script', get_theme_file_uri() . "/custom-table/assets/js/tabloide-personalized-script.js", ["jquery"], null, true);
    wp_enqueue_script('table-personalized-additional-script', get_theme_file_uri() . "/custom-table/assets/js/personalized-additional-script.js", ["jquery"], null, true);
    wp_enqueue_script('custom-fontawesome', "https://kit.fontawesome.com/06fe12cea6.js", [], "", false);

    wp_localize_script('table-personnalize-script', 'ajax_object', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'picture_good' => __( "Votre image n'est pas conforme.", 'shoptimizer' ),
        'unaivalable' => __( "Ce produit n'est pas disponible avec cette configuration, veuillez modifier votre configuration ou nous contacter.", 'shoptimizer' ),
        'error' => __("Une erreur inattendue s'est produite. Veuillez rafraîchir votre page et réessayer.", "shoptimizer")
    ]);
    wp_localize_script( 'table-personalized-additional-script', 'i18n',
    array(
        'picture_empty' => __( "Veuillez charger une image d'au moins 1920 x 1080 px.", 'shoptimizer' ),
        'picture_not_good' => __( "Votre image n'est pas conforme.", 'shoptimizer' ),
    ) 
    );
}


// Enqueue Style and Script
add_action("wp_enqueue_scripts", "my_script", 999);

// Ajax for checking picture uploaded
add_action("wp_ajax_tabloide_check_picture_uploaded", 'tabloide_check_picture_uploaded');
add_action("wp_ajax_nopriv_tabloide_check_picture_uploaded", "tabloide_check_picture_uploaded");

// Ajax to add to cart custom table
add_action("wp_ajax_tabloide_add_to_cart_product_personalized", 'tabloide_add_to_cart_product_personalized');
add_action("wp_ajax_nopriv_tabloide_add_to_cart_product_personalized", "tabloide_add_to_cart_product_personalized");

// Ajax get price and variation id of product
add_action("wp_ajax_tabloide_get_price_and_variation_id", 'tabloide_get_price_and_variation_id');
add_action("wp_ajax_nopriv_tabloide_get_price_and_variation_id", "tabloide_get_price_and_variation_id");

// Create shortcode for page custom table
function add_personalized_form($atts)
{
    $atts = shortcode_atts(array(
        'id' => '0',
    ), $atts, 'add_personalized_form');
    $id = "{$atts['id']}";

    $products = wc_get_product($id);
    $size = $products->default_attributes['pa_taille'];
    $support = $products->default_attributes['pa_support'];
    $bgImage = wp_get_attachment_image_url($products->image_id, "");
    require "partials/view_personalized_form.php";

}

//
add_shortcode("personalized", "add_personalized_form");

//
function personalized_move_uploaded_file($tmp_name, $uploadfile)
{
    if (move_uploaded_file($tmp_name, $uploadfile)) {
        return true;
    }
    return false;
}

// Check image quality
function personalized_check($api, $id, $generate, $name, $picture, $uploadfile)
{
    switch ($api) {
        case 'failure':
            wp_send_json([
                "id" => $id, 
                "status" => "error", 
                "error" => "014", 
                "type" => "failure",
                "message" => __("Un problème inattendu est survenu lors du chargement de l'image, veuillez actualiser votre page et réessayer.", "shoptimizer"), 
                "picture_id" => $generate, 
                "name" => $name,
                "picture" => $picture
            ]);
            unlink($uploadfile);
            wp_die();
            break;
        case 'sharpness':
            wp_send_json([
                "id" => $id, 
                "status" => "error", 
                "error" => "015", 
                "type" => "sharpness",
                "message" => __("Votre image n'est pas de bonne qualité.", "shoptimizer"), 
                "picture_id" => $generate, 
                "name" => $name,
                "picture" => $picture
            ]);
            unlink($uploadfile);
            wp_die();
            break;
        case 'success':
            wp_send_json([
                "id" => $id, 
                "status" => "success", 
                "error" => "003", 
                "type" => "sharpness",
                "message" => __("Votre image est correcte.", "shoptimizer"), 
                "picture_id" => $generate, 
                "name" => $name,
                "picture" => $picture
            ]);
            wp_die();
            break;
    }
}

// Upscale Image
function personalized_upscaling(string $picture, int $level)
{
    $perso = new Perso();
    $img = file_get_contents($picture);
    $data = base64_encode($img);
    $json = $perso->upscale($data, $level);
    $output = json_decode($json);
    return $output->output_url;
}

// Convert Image 

function custom_table_convert_image(string $type,  string $uploadfile, string $uploaddir, string $name) {
    $perso = new Perso();
    switch ($type) {
        case 'image/heic':
            $format = "heic";
            break;
        case 'image/webp':
            $format = "webp";
            break;
        case 'image/png':
            $format = "png";
            break;
    }
    $perso->convert($uploaddir . $name, $uploaddir, $format);
    unlink($uploadfile);
    $justName = explode(".", $name);
    $name = $justName[0] . ".jpg";
    return $name;
}


// Convert DPI
function convert_dpi (string $picture, string $uploadfile) {
    $image = imagecreatefromjpeg($picture);
    imageresolution($image, 300, 300);
    imagejpeg($image, $uploadfile, 100);
    imagedestroy($image);
}

/**
 * Check picture uploaded
 */
function tabloide_check_picture_uploaded()
{
    if (isset($_POST['tabloide-personalized-id'])) {
        $id = $_POST['tabloide-personalized-id'];
        $generate = wp_rand(100000, 9999999999);
        $uploaddir = WP_CONTENT_DIR . '/uploads/personalized/';
        $name = $file = $picture = "";

        if (isset($_POST["tabloide-link-upload"]) && !empty($_POST["tabloide-link-upload"])) {
            $inputFileValue = $_POST["tabloide-link-upload"];
            if (!filter_var($inputFileValue, FILTER_VALIDATE_URL)) {
                wp_send_json([
                    "id" => "", 
                    "status" => "error", 
                    "error" => "017", 
                    "message" => __("Le lien que vous avez fourni est incorrect.", "shoptimizer"), 
                    "picture_id" => "", 
                    "name" => "",
                    "picture" => ""
                ]);
                wp_die();
            }

            $imageInfo = getimagesize($inputFileValue);
            $image = explode("/", $imageInfo['mime']);
            $name = $generate . "." . $image[1];
            $uploadfile = $uploaddir . $name;
            $file = $uploadfile;
            file_put_contents($uploadfile, file_get_contents($inputFileValue));
            $picture = site_url('/wp-content/uploads/personalized/' . $name);

            $typeImage = getimagesize($picture)['mime'];

            if ($typeImage == "image/heic" || $typeImage == "image/webp" || $typeImage == "image/png") {
                $name = custom_table_convert_image ($typeImage,  $uploadfile, $uploaddir, $name);
            }
            $uploadfile = $uploaddir . $name;
            $file = $uploadfile;
            $picture = site_url('/wp-content/uploads/personalized/' . $name);
            list($width, $height) = getimagesize($picture);

        } else if (isset($_FILES['tabloide-upload']) && !empty($_FILES['tabloide-upload'])) {
            $inputFile = $_FILES['tabloide-upload'];
            $inputFileValue = $inputFile['name'];
            $name = $generate . "_" . basename($inputFileValue);
            $typeImage = $_FILES['tabloide-upload']['type'];
            $uploadfile = $uploaddir . $name;
            personalized_move_uploaded_file($inputFile['tmp_name'], $uploadfile);

            if ($typeImage == "image/heic" || $typeImage == "image/webp" || $typeImage == "image/png") {
                $name = custom_table_convert_image ($typeImage,  $uploadfile, $uploaddir, $name);
            }

            $uploadfile = $uploaddir . $name;
            $file = $uploadfile;
            $picture = site_url('/wp-content/uploads/personalized/' . $name);
            list($width, $height) = getimagesize($picture);
        }

        // On vérifie si la valeur des champs renseignés par l'utilisateur n'est pas vide
        if (!empty($inputFileValue)) {
            if (!empty($file) && !empty($picture)) {
                list($width, $height) = getimagesize($picture);
                $typeImage = getimagesize($picture)['mime'];

                // On upscale si la dimension de l'image est inférieur à 1920 x 1080px
                if ($width <= 1920 && $height <= 1080) {
                    $json = personalized_upscaling($file, 4);
                    if ($json) {
                        unlink($uploadfile);
                        list($typeFile, $formatImage) = explode("/", $typeImage);
                        $name = $generate . "." . $formatImage;
                        $uploadfile = $uploaddir . $name;
                        file_put_contents($uploadfile, file_get_contents($json));

                        $picture = site_url('/wp-content/uploads/personalized/' . $name);
                        list($width, $height) = getimagesize($picture);
                        $typeImage = getimagesize($picture)['mime'];
                    }
                }

                // On vérifie la qualité, la taille et le format de l'image
                if ($width > 1920 && $height > 1080) {

                    $perso = new Perso();
                    $api = $perso->check($uploadfile, 0.40);
                    if ($api == "success") {
                        if ($typeImage == "image/png" || $typeImage == "image/jpeg"
                            || $typeImage == "image/jpg") {
                            convert_dpi ($picture, $uploadfile, $width, $height);
                            personalized_check($api, $id, $generate, $name, $picture, $uploadfile);
                        } else {
                            unlink($uploadfile);
                            $picture = get_template_directory_uri() . "/pictures/default-placeholder.png";
                            wp_send_json([
                                "id" => $id, 
                                "status" => "error", 
                                "error" => "001", 
                                "message" => __("Le format de votre fichier n'est pas accepté, votre fichier doit être de type : jpeg, heic, png, webp.", "shoptimizer"), 
                                "picture_id" => $generate, 
                                "name" => $name,
                                "picture" => $picture
                            ]);
                            wp_die();
                        }
                    } else {
                        unlink($uploadfile);
                        $picture = get_template_directory_uri() . "/pictures/default-placeholder.png";
                        wp_send_json([
                            "id" => $id, 
                            "status" => "error", 
                            "error" => "007", 
                            "message" => __("Votre image n'est pas de bonne qualité.", "shoptimizer"), 
                            "picture_id" => $generate, 
                            "name" => $name,
                            "picture" => $picture
                        ]);
                        wp_die();
                    }
                } else {
                    unlink($uploadfile);
                    $picture = get_template_directory_uri() . "/pictures/default-placeholder.png";
                    wp_send_json([
                        "id" => $id, 
                        "status" => "error", 
                        "error" => "002", 
                        "message" => __("La dimension de votre image est trop petite, elle doit être d'au moins 1920 x 1080px.", "shoptimizer"), 
                        "picture_id" => $generate, 
                        "name" => $name,
                        "picture" => $picture
                    ]);
                    wp_die();
                }

            } else {
                unlink($uploadfile);
                $picture = get_template_directory_uri() . "/pictures/default-placeholder.png";
                wp_send_json([
                    "id" => $id, 
                    "status" => "error", 
                    "error" => "006", 
                    "message" => __("Une erreur s'est produite. Veuillez réessayer.", "shoptimizer"), 
                    "picture_id" => $generate, 
                    "name" => $name,
                    "picture" => $picture
                ]);
                wp_die();
            }
        } else {
            unlink($uploadfile);
            $picture = get_template_directory_uri() . "/pictures/default-placeholder.png";
            wp_send_json([
                "id" => $id, 
                "status" => "error", 
                "error" => "003", 
                "message" => __("Aucune image n'a été chargée.", "shoptimizer"), 
                "picture_id" => $generate, 
                "name" => $name,
                "picture" => $picture
            ]);
            wp_die();
        }
    }
    wp_send_json([
        "id" => $id, 
        "status" => "error", 
        "error" => "006", 
        "message" => __("Une erreur s'est produite. Veuillez réessayer.", "shoptimizer"), 
        "picture_id" => $generate, 
        "name" => $name,
        "picture" => $picture
    ]);
    wp_die();
}

// Display custom cart item meta data (in cart and checkout)
add_filter('woocommerce_get_item_data', 'display_cart_item_custom_meta_data', 10, 2);
function display_cart_item_custom_meta_data($item_data, $cart_item)
{
    $meta_key_PIC = 'Image';
    $meta_key_PDF = 'Pdf';
    if (isset($cart_item['personalized_picture']) && isset($cart_item['personalized_picture'][$meta_key_PIC])) {
        $item_data[] = array(
            'key' => $meta_key_PIC,
            'value' => $cart_item['personalized_picture'][$meta_key_PIC],
        );
    }
    if (isset($cart_item['personalized_pdf']) && isset($cart_item['personalized_pdf'][$meta_key_PDF])) {
        $item_data[] = array(
            'key' => $meta_key_PDF,
            'value' => $cart_item['personalized_pdf'][$meta_key_PDF],
        );
    }
    return $item_data;
}

// Save cart item custom meta as order item meta data and display it everywhere on orders and email notifications.
add_action('woocommerce_checkout_create_order_line_item', 'save_cart_item_custom_meta_as_order_item_meta', 10, 4);
function save_cart_item_custom_meta_as_order_item_meta($item, $cart_item_key, $values, $order)
{
    $meta_key_PIC = 'Image';
    $meta_key_PDF = 'Pdf';
    if (isset($values['personalized_picture']) && isset($values['personalized_picture'][$meta_key_PIC])) {
        $item->update_meta_data($meta_key_PIC, $values['personalized_picture'][$meta_key_PIC]);
    }
    if (isset($values['personalized_pdf']) && isset($values['personalized_pdf'][$meta_key_PDF])) {
        $item->update_meta_data($meta_key_PDF, $values['personalized_pdf'][$meta_key_PDF]);
    }
}

// Get price by product variation
function get_price_by_variation(int $product_id, array $variationArray, string $key)
{
    $args = array(
        'post_type' => 'product_variation',
        'post_status' => array('private', 'publish'),
        'numberposts' => -1,
        'orderby' => 'menu_order',
        'order' => 'asc',
        'post_parent' => $product_id,
    );
    $data = "error";
    $variations = get_posts($args);
    foreach ($variations as $variation) {
        // get variation ID
        $variation_ID = $variation->ID;

        // get variations meta
        $product_variation = new WC_Product_Variation($variation_ID);

        if ($variationArray[0] == $product_variation->attributes['pa_taille']
            && $variationArray[1] == $product_variation->attributes['pa_support']) {

            // get variation price
            $variation_price = $product_variation->get_price();

            // get_post_meta( $variation_ID , '_text_field_date_expire', true );
            if ($key == "_price") {
                $data = "error";
                if (!empty($variation_price)) {
                    $data = $variation_price;
                }
            } else if ($key == "_variation_id") {
                $data = "error";
                if (!empty($variation_ID)) {
                    $data = $variation_ID;
                }
            }
        }
    }
    return $data;
}

// Get price and variation id by the product id
function tabloide_get_price_and_variation_id()
{
    $support = $_POST['tabloide-support-input'];
    $size = $_POST['tabloide-size-input'];
    $product_id = $_POST['tabloide-personalized-id'];

    $price = get_price_by_variation($product_id, [$size, $support], "_price");
    $variation_id = get_price_by_variation($product_id, [$size, $support], "_variation_id");
    wp_send_json([
        "status" => "success", 
        "price" => $price, 
        "message" => "", 
        "variation_id" => $variation_id
    ]);
    wp_die();
}

/**
 * Check picture uploaded
 */
function tabloide_add_to_cart_product_personalized()
{
    $product_id = $_POST['tabloide-personalized-id'];
    $variation_id = $_POST["tabloide-personalized-attribute-id"];
    $quantity = $_POST['tabloide-personalized-quantity'];
    $personalized_id = $_POST['personalized_id'];
    $personalized_picture = $_POST['personalized_picture'];
    $size = $_POST['tabloide-size-input'];
    $position = "landscape";
    if ($_POST['tabloide-disposition-check']) {
        $position = "portrait";
    }
    $personalized_pdf = get_permalink(get_page_by_path('generate-personalized-pdf')) . "?picture=$personalized_picture" . "&position=$position" . "&size=$size";

    if (!empty($product_id) && !empty($variation_id) && !empty($quantity) && !empty($personalized_picture)) {
        if (WC()->cart->add_to_cart($product_id, $quantity, $variation_id, [
            'id' => $product_id,
        ], [
            "personalized_id" => ['ID' => $personalized_id],
            "personalized_picture" => ['Image' => $personalized_picture],
            "personalized_pdf" => ['Pdf' => $personalized_pdf],
        ]
        )) {
            $redirect = wc_get_cart_url();
            wp_send_json([
                "id" => $product_id, 
                "status" => "success", 
                "error" => "011", 
                "message" => __("Votre tableau a bien été ajouté dans votre panier.", "shoptimizer"), 
                "redirect" => $redirect
            ]);
        } else {
            wp_send_json([
                "id" => $product_id, 
                "status" => "error", 
                "error" => "012", 
                "message" => __("Votre tableau n'a pas pu être inséré dans votre panier, veuillez réessayer.", "shoptimizer"), 
                "redirect" => ""
            ]);
        }
    } else {
        wp_send_json([
            "id" => $product_id, 
            "status" => "error", 
            "error" => "013", 
            "message" => __("Une erreur inattendue s'est produite, veuillez actualiser votre page et réessayer.", "shoptimizer"), 
            "redirect" => ""
        ]);
    }
    wp_die();
}

// Translation
function tabloide_load_theme_textdomain() {
    load_child_theme_textdomain( 'shoptimizer', get_template_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'tabloide_load_theme_textdomain' );