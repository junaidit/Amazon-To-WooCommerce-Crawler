<?php
/**
 * Plugin Name: Amazon To WooCommerce Crawler
 * Plugin URI: 
 * Description: This plugin add facility to import products from amazon to woocommerce 
 * Version: 1.00
 * Author: Junaid Ali
 * Author URI: https://github.com/junaidit
 * License: GPL2
 * Created On: 10-09-2017
 * Updated On: 11-16-2017
 */


add_action('admin_menu', 'amazon_control_menu');
add_action('init', 'amazon_crawler');

function amazon_add_product_brands(){
   register_taxonomy('product_brand',array('product'), array(
      'labels' => array(
     'name' => esc_html__('Brands', 'atwp'),
     'singular_name' => esc_html__('Brand', 'atwp'),
     'search_items' => esc_html__('Search Brands', 'atwp'),
     'all_items' => esc_html__('All Brands', 'atwp'),
     'parent_item' => esc_html__('Parent Brand', 'atwp'),
     'parent_item_colon' => esc_html__('Parent Brand:', 'atwp'),
     'edit_item' => esc_html__('Edit Brand', 'atwp'), 
     'update_item' => esc_html__('Update Brand', 'atwp'),
     'add_new_item' => esc_html__('Add New Brand', 'atwp'),
     'new_item_name' => esc_html__('New Brand', 'atwp'),
     'not_found' => esc_html__('No Brand Found', 'atwp' ),
     'menu_name' => esc_html__('Brands', 'atwp')
      ),
      'hierarchical' => true,
      'query_var' => true,
      'public' => true,
      'show_tagcloud' => true,
      'show_admin_column' => true,
      'show_in_nav_menus' => true,
      'sort' => '',
      'rewrite' => array('slug' => 'brand','with_front' => false),
      'show_ui' => true

      )); 
}

add_action('init','amazon_add_product_brands', 0);

function amazon_control_menu() {
  add_submenu_page('options-general.php', 'amazong-to-wp', 'Amazon To Wp', 'manage_options', 'amazon-control-menu', 'amazon_control_options');
}



//add_action( 'wp', 'amazon_crawler' );
function amazon_control_options() {
if(isset($_GET['imported'])){
	echo "<p>Products Imported successfully.</p>";
}


		?>

<form method="GET">
	<table>
		<tr><th>Amazon Crawler</th></tr>
		<tr><td><label>Amazon Page URL:</label></td><td><input type="text" name="amazon_url" /></td></tr>
		<tr><td><label>Product Category:</label></td><td><input type="text" name="product_cat" /></td></tr>
		<tr><td><input type="submit" name="Fetch Products"></td></tr>
	</table>
</form>
		<?php
}


function amazon_crawler(){

	include (dirname ( __FILE__ ) . "/includes/crawler.class.php");
	$crawler	=	new Crawler();
	
	if( isset( $_GET["amazon_url"] ) && !empty( $_GET["amazon_url"] ) ) {
	set_time_limit(0);
		$product_category = $_GET['product_cat'];
			
		$base_url = $_GET["amazon_url"];
		//$url = "https://www.amazon.com/s/ref=sr_in_-2_p_89_21?fst=as%3Aoff&rh=n%3A2619533011%2Cn%3A%212619534011%2Cn%3A2975241011%2Cn%3A2975265011%2Cp_89%3AMeow+Mix&bbn=2975265011&ie=UTF8&qid=1475582783&rnid=2528832011";
		$contents = $crawler->getContent($base_url);
		if(empty($contents['ERR']))
			{

				$listingPage	=	$contents['EXE'];
			}
		else{
			echo $contents['ERR'] . PHP_EOL;
			exit();
		}
		$dom = new DOMDocument();
		@$dom->loadHTML($listingPage);
		$xPath = new DOMXPath($dom);
		$classname = "pagnDisabled";
		$elements = $xPath->query("//*[contains(@class, '$classname')]");
		foreach ($elements as $e) {
			$pages = $e->textContent;
		}
		//echo "Pages: ".$pages;
		//echo "<pre>";print_r($pages);echo "</pre>";die;
		
		$pages = 8;
		for( $i = 1; $i <= $pages; $i++ ) {
			echo $i;
			$url = $base_url."&page=".$i;

			$contents2 = $crawler->getContent($url);
			if(empty($contents2['ERR']))
				{
					$listingPage2	=	$contents2['EXE'];
				}
			else{
				echo $contents2['ERR'] . PHP_EOL;
				exit();
			}


			$dom2 = new DOMDocument();
			@$dom2->loadHTML($listingPage2);
			$xPath2 = new DOMXPath($dom2);
			$classname="s-access-detail-page";
			$elements = $xPath2->query("//*[contains(@class, '$classname')]");
			
			foreach ($elements as $e) {
				$link = $e->getAttribute('href');
				/*$e->setAttribute("href", $lnk);
				$newdoc = new DOMDocument;
				$e = $newdoc->importNode($e, true);
				$newdoc->appendChild($e);
				$html = $newdoc->saveHTML();
				echo $html;*/
				//echo $link;die;
				

				$contents3 = $crawler->getContent($link);
				if(empty($contents3['ERR']))
					{

						$detailPage	=	$contents3['EXE'];
					}
				else{
					echo $contents3['ERR'] . PHP_EOL;
					exit();
				}



				$single_product = new DOMDocument();
				@$single_product->loadHTML( $detailPage );
				$singleXpath = new DOMXPath($single_product);
				$priceElements = $singleXpath->query('//span[@id="priceblock_ourprice"]');


				$brandArr = $singleXpath->query('//a[@id="brand"]');
				if($brandArr->length > 0){
					$product_brand = $brandArr->item(0)->nodeValue;
					$product_brand = trim($product_brand);
				}

				
				//echo "<pre>"; print_r( $single_product->getElementById( "imgTagWrapperId" ) ); echo "</pre>";
				if($priceElements->length > 0){
					$product_price = $priceElements->item(0)->nodeValue;
					$product_price = str_replace("$", "", $product_price);

					$product_price = $product_price * 102;

				}

				$product_title = trim( $single_product->getElementById("productTitle")->textContent );

				$product_image = $single_product->getElementById( "imgTagWrapperId" );
				foreach($product_image->getElementsByTagName('img') as $element) 
					$product_image = $element->getAttribute('src');
				/*foreach($product_image->childNodes as $child) {
					#echo "<pre>";print_r($child);echo "<pre>";
					$product_image = $child->ownerDocument->saveHTML($child);
				}*/
				$product_bullets = $single_product->getElementById( "feature-bullets" )->nodeValue;
				//echo "<pre>";print_r( $product_bullets );echo "<pre>";
				echo $product_title."<br/>";
				//echo $product_image."<br/>";
				//echo $product_bullets."<br/>";
				
				$pos = strrpos($product_image, '/');
				$product_image_title = $pos === false ? $product_image : substr($product_image, $pos + 1);
				//echo $product_image_title;
				$product_image_title = str_replace(" ","_",$product_image_title);
				$product_image_title = str_replace(".","",$product_image_title);
				$product_image_title = str_replace("jpg",".jpg",$product_image_title);
				//echo $product_image_title;
				$product_image = file_get_contents( $product_image );
				$product_image = file_put_contents( dirname(__FILE__)."/../../uploads/".$product_image_title , $product_image);
				//echo dirname(__FILE__)."/../../uploads/".$product_image_title;
				//die;
				
				if( get_page_by_title( $product_title, OBJECT, 'product' ) == NULL ) {
					//CREATE SUBSCRIPTION PRODUCT
					$my_post = array(
									'post_content' => $product_bullets,
									'post_name' => $product_title,
									'post_title' => $product_title,
									'post_status' => 'publish',
									'post_type' => 'product',
									'post_author' => 1,
									'ping_status' => 'closed',
									'post_excerpt' => '',
									'post_date' => date('Y-m-d H:i:s'),
									'post_date_gmt' => '0000-00-00 00:00:00',
									'comment_status' => 'open'
									);
					//print_r($my_post);
			 
					// Insert the post into the database
					$product_id = wp_insert_post( $my_post );

					// $filename should be the path to a file in the upload directory.
					
					/*if(in_array('product-mixes',$subscription_product_cats)) {
						$filename = '/public_html/thinkitdrinkit/wp-content/uploads/2016/01/universal.png';
					} else {
						$filename = wp_get_attachment_url( get_post_thumbnail_id($simple_product_id) );
					}*/
					
					$filename = $product_image_title;
					// The ID of the post this attachment is for.
					$parent_post_id = $product_id;
					 
					// Check the type of file. We'll use this as the 'post_mime_type'.
					$filetype = wp_check_filetype( basename( $filename ), null );
					 
					// Get the path to the upload directory.
					$wp_upload_dir = wp_upload_dir();
					 
					// Prepare an array of post data for the attachment.
					$attachment = array(
						'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ), 
						'post_mime_type' => $filetype['type'],
						'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
						'post_content'   => '',
						'post_status'    => 'inherit'
					);
					 
					// Insert the attachment.
					$attach_id = wp_insert_attachment( $attachment, $filename, $parent_post_id );
					 
					// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
					require_once( ABSPATH . 'wp-admin/includes/image.php' );
					 
					// Generate the metadata for the attachment, and update the database record.
					$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
					wp_update_attachment_metadata( $attach_id, $attach_data );
					 
					set_post_thumbnail( $parent_post_id, $attach_id );
					
					update_post_meta($product_id, '_edit_last', '1');
					update_post_meta($product_id, '_edit_lock', time().":".'1');
					update_post_meta($product_id, '_visibility', 'visible');
					update_post_meta($product_id, '_stock_status', 'instock');
					update_post_meta($product_id, 'total_sales', '0');
					update_post_meta($product_id, '_downloadable', 'no');
					update_post_meta($product_id, '_virtual', 'no');
					update_post_meta($product_id, '_price', $product_price);
					update_post_meta($product_id, '_regular_price', $product_price);
					update_post_meta($product_id, '_sale_price', '');
					update_post_meta($product_id, '_purchase_note', '');
					update_post_meta($product_id, '_featured', 'no');
					update_post_meta($product_id, '_weight', '');
					update_post_meta($product_id, '_length', '');
					update_post_meta($product_id, '-_width', '');
					update_post_meta($product_id, '_height', '');
					update_post_meta($product_id, '_sku', "");
					update_post_meta($product_id, '_product_attributes', array());
					update_post_meta($product_id, '_sale_price_dates_from', '');
					update_post_meta($product_id, '_sale_price_dates_to', '');
					update_post_meta($product_id, '_sold_individually', '');
					update_post_meta($product_id, '_manage_stock', 'no');
					update_post_meta($product_id, '_backorders', 'no');
					update_post_meta($product_id, '_stock', '');
					update_post_meta($product_id, '_upsell_ids', array());
					update_post_meta($product_id, '_crosssell_ids', array());
					update_post_meta($product_id, '_product_version', '2.4.7');
					update_post_meta($product_id, '_product_image_gallery', '');
					update_post_meta($product_id, '_type', 'bundle');
				
					wp_set_object_terms( $product_id, 'simple', 'product_type' );
					wp_set_object_terms( $product_id, $product_category, 'product_cat' );
					wp_set_object_terms( $product_id, $product_brand, 'product_brand' );
					
				}
				
				// STOPS AFTER FIRST ITERATION
				//break;
			}
			//break;
		}

		$redirect_URL = admin_url() .'options-general.php?page=amazon-control-menu&imported=true';
		 wp_redirect( $redirect_URL );
		 exit();
	}
}
?>