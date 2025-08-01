<?php

/**
 * Template Name: Blog Page
 */
get_header();

$all_posts = new WP_Query([
    'posts_per_page' => -1,
    'post_status' => 'publish',
]);
$posts_data = [];
if ($all_posts->have_posts()) {
    while ($all_posts->have_posts()) {
        $all_posts->the_post();
        $cats = wp_get_post_categories(get_the_ID(), ['fields' => 'names']);
        $posts_data[] = [
            'id' => get_the_ID(),
            'title' => get_the_title(),
            'permalink' => get_permalink(),
            'date' => get_the_date('F j, Y'),
            'thumb' => get_the_post_thumbnail_url(get_the_ID(), 'medium'),
            'excerpt' => get_field('short_text') ?: get_the_excerpt(),
            'cats' => $cats,
        ];
    }
    wp_reset_postdata();
}


$categories = get_categories([
    'hide_empty' => true,
]);
?>

<main class="blog-main">
    <section class="blog-hero">
        <div class="blog-hero-bg">
            <?php

            $page_for_posts = get_option('page_for_posts');
            $post_obj = get_post($page_for_posts);
            if ($post_obj) {
                $blocks = parse_blocks($post_obj->post_content);
                if (!empty($blocks)) {

                    $first_block = array_shift($blocks);
                    $first_content = apply_filters('the_content', render_block($first_block));
                    echo '<h1 class="blog-hero-title">' . wp_strip_all_tags($first_content) . '</h1>';

                    $rest_content = '';
                    foreach ($blocks as $block) {
                        $rest_content .= apply_filters('the_content', render_block($block));
                    }
                    echo '<div class="blog-hero-desc">' . $rest_content . '</div>';
                }
            }
            ?>
        </div>
    </section>

    <section class="blog-posts">
        <div class="blog-categories-wrap">
            <div class="blog-categories-row">
                <div class="blog-categories">
                    <button class="blog-cat-btn active" data-cat="all">All</button>
                    <?php foreach ($categories as $cat): ?>
                        <button class="blog-cat-btn" data-cat="<?php echo esc_attr($cat->name); ?>">
                            <?php echo esc_html($cat->name); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <form class="blog-search-form" onsubmit="return false;">
                    <span class="blog-search-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M7.66536 14C11.1632 14 13.9987 11.1645 13.9987 7.66668C13.9987 4.16887 11.1632 1.33334 7.66536 1.33334C4.16756 1.33334 1.33203 4.16887 1.33203 7.66668C1.33203 11.1645 4.16756 14 7.66536 14Z" stroke="#302F34" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M14.6654 14.6667L13.332 13.3333" stroke="#302F34" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <input type="text" class="blog-search-input" placeholder="Search" id="blog-search-input" />
                </form>
            </div>
        </div>

        <div class="blog-posts-list" id="blog-posts-list">

        </div>
        <div class="blog-show-more-wrap">
            <button id="blog-show-more" class="show-more-btn btn "><svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11.1654 9.10004V11.9C11.1654 14.2334 10.232 15.1667 7.8987 15.1667H5.0987C2.76536 15.1667 1.83203 14.2334 1.83203 11.9V9.10004C1.83203 6.76671 2.76536 5.83337 5.0987 5.83337H7.8987C10.232 5.83337 11.1654 6.76671 11.1654 9.10004Z" stroke="#302F34" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M15.1654 5.10004V7.90004C15.1654 10.2334 14.232 11.1667 11.8987 11.1667H11.1654V9.10004C11.1654 6.76671 10.232 5.83337 7.8987 5.83337H5.83203V5.10004C5.83203 2.76671 6.76536 1.83337 9.0987 1.83337H11.8987C14.232 1.83337 15.1654 2.76671 15.1654 5.10004Z" stroke="#302F34" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                Show more</button>
        </div>
    </section>


</main>


<?php get_footer(); ?>

<script>
    const allPosts = <?php echo json_encode($posts_data); ?>;
    const postsPerPage = 9;
    let currentPage = 1;
    let currentCat = 'all';
    let currentSearch = '';

    document.getElementById('blog-search-input').addEventListener('input', function() {
        currentSearch = this.value.trim().toLowerCase();
        currentPage = 1;
        renderPosts();
    });

    function renderPosts() {
        const list = document.getElementById('blog-posts-list');
        list.innerHTML = '';
        let filtered = allPosts;
        if (currentCat !== 'all') {
            filtered = filtered.filter(post => post.cats.includes(currentCat));
        }
        if (currentSearch.length > 0) {
            filtered = filtered.filter(post =>
                post.title.toLowerCase().includes(currentSearch) ||
                post.excerpt.toLowerCase().includes(currentSearch)
            );
        }
        const toShow = filtered.slice(0, currentPage * postsPerPage);

        if (toShow.length === 0) {
            list.innerHTML = `<div class="blog-no-results">Nothing found. Try changing your query.</div>`;
            document.getElementById('blog-show-more').style.display = 'none';
            return;
        }

        toShow.forEach(post => {
            let catsHtml = '';
            post.cats.forEach(cat => {
                catsHtml += `<span class="blog-post-cat">${cat}</span> `;
            });

            list.innerHTML += `
      <a href="${post.permalink}" class="blog-post-card">
        <div class="blog-post-thumb">
          ${post.thumb ? `<img src="${post.thumb}" alt="${post.title}">` : ''}
        </div>
        <div class="blog-post-meta">${post.date}</div>
        <div class="blog-post-title">${post.title}</div>
        <div class="blog-post-excerpt">${post.excerpt}</div>
        <div class="blog-post-cats">${catsHtml}</div>
      </a>`;
        });

        document.getElementById('blog-show-more').style.display =
            (filtered.length > toShow.length) ? 'flex' : 'none';
    }

    document.querySelectorAll('.blog-cat-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.blog-cat-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentCat = this.dataset.cat;
            currentPage = 1;
            renderPosts();
        });
    });

    document.getElementById('blog-show-more').addEventListener('click', function() {
        currentPage++;
        renderPosts();
    });

    renderPosts();
</script>