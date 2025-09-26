<?php
/**
 * Template Name: Courses
 */
get_header();
$courses = new WP_Query([
  'post_type' => 'course',
  'posts_per_page' => -1,
  'order' => 'ASC',
]);
$posts = $courses->posts;
$categories = get_terms(['taxonomy' => 'course_cat', 'hide_empty' => false]);
?>
<main>
  <section class="courses-section">
    <div class="container">
      <div class="courses-section__top">
        <h1 class="title"><?= get_field('courses_page_title') ?></h1>
        <p class="paragraph"><?= get_field('courses_page_text') ?></p>
      </div>
      <div class="courses-section__filters">
        <?php if ($categories && is_array($categories) && !empty($categories)) {
          echo "<button class='filter active' data-slug='all'>All</button>";
          foreach ($categories as $category) {
            $name = $category->name;
            $slug = $category->slug;
            echo "<button class='filter' data-slug='$slug'>$name</button>";
          }
        } ?>
      </div>
      <div class="courses-section__grid">
        <?php if ($posts && is_array($posts) && !empty($posts)) {
          foreach ($posts as $post) {
            get_template_part('template-parts/shared/course-card', null, ['post' => $post]);
            get_template_part('template-parts/shared/course-card', null, ['post' => $post]);
            get_template_part('template-parts/shared/course-card', null, ['post' => $post]);
          }
        } ?>
      </div>
    </div>
  </section>
</main>
<?php get_footer(); ?>
