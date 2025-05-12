<?php

add_action('admin_menu', function () {
  add_menu_page(
    'Google Reviews',
    'Google Reviews',
    'manage_options',
    'reviews_sidebar_metabox',
    "render_reviews_sidebar_metabox",
    'dashicons-networking',
    27
  );
});

function render_reviews_sidebar_metabox() { 
  $data = getPlaceReviews(); 
  $reviews = $data["reviews"];
 
  if (empty($reviews)) { 
    echo '<p>No reviews found.</p>'; 
    return; 
  }
 
  if (isset($_POST['submit_reviews']) && check_admin_referer('save_selected_reviews')) { 
    $selected = isset($_POST['review']) ? (array) $_POST['review'] : []; 
    update_option('selected_google_reviews', $selected); 
    echo '<div class="updated notice"><p>Selected reviews saved.</p></div>'; 
    $checked_reviews = [];
    foreach ($reviews as $index => $review) {
      $review_id = "review_$index";
      if (in_array($review_id, $selected)) {
        $review['review_id'] = $review_id;
        $checked_reviews[] = $review;
      }
    }
    update_option('selected_google_reviews_data', $checked_reviews);
  } 
 
  $saved_reviews = get_option('selected_google_reviews', []); 
  echo '<form method="post">'; 
  echo '<div class="my-custom-button-wrapper"><button type="submit" name="submit_reviews" class="button button-primary">Save Selected Reviews</button></div>'; 
  wp_nonce_field('save_selected_reviews'); 
  echo '<div class="reviews-list">'; 
 
  foreach ($reviews as $index => $review) { 
    $review_id = esc_attr("review_$index"); 
    $name = esc_html($review['author_name']); 
    $profile_photo_url = esc_url($review['profile_photo_url']); 
    $rating = intval($review['rating']); 
    $text = esc_html($review['text']); 
    $author_url = esc_url($review['author_url']); 
    $relative_time_description = esc_html($review['relative_time_description']); 
    $stars = str_repeat('★', $rating); 
    $checked = in_array($review_id, $saved_reviews) ? 'checked' : ''; 
 
    echo "<label class='custom-review-card' for='$review_id'> 
      <input class='custom-review-card__input' type='checkbox' name='review[]' id='$review_id' value='$review_id' $checked> 
      <div class='custom-review-card__bubble'> 
        <div class='custom-review-card__stars'>$stars</div> 
        <div class='custom-review-card__text'>$text</div> 
      </div> 
      <a href='$author_url' target='_blank' class='custom-review-card__footer'> 
        <img src='$profile_photo_url' alt='$name' class='custom-review-card__avatar'> 
        <div> 
          <div class='custom-review-card__name'>$name</div> 
          <div class='custom-review-card__time'>$relative_time_description</div> 
        </div> 
      </a> 
    </label>"; 
  } 
 
  echo '</div>'; 
  echo '</form>'; 
}



add_action('admin_enqueue_scripts', function () {
  wp_enqueue_style(
    'google-admin-styles',
    get_template_directory_uri() . '/inc/admin/css/google.css',
    [],
    '1.0.0'
  );
});

