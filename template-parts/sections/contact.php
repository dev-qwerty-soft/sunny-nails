<?php
  $cords = json_encode([
    'lat' => get_field('map_lat', 'option') ?: 0,
    'lng' => get_field('map_lng', 'option') ?: 0,
  ]);
?>

<div class="contact-section">
  <div class="container">
    <div class="contact-section__content">
      <div class="contact-section__text">
        <h2 class="title">Contacts</h2>
        <p class="paragraph">We’d love to hear from you! Whether you have a question, need to book an appointment, or want to give feedback, feel free to reach out.</p>
      </div>
      <div class="info-item">
        <img src="<?= getUrl("images/contacts.svg") ?>" alt="">
        <div class="info-item__content">
          <a href="#" class="info-item__item">
            <b>+65 1234 5678</b>
          </a>
        </div>
      </div>
      <div class="info-item">
        <img src="<?= getUrl("images/contacts (1).svg") ?>" alt="">
        <div class="info-item__content">
          <a href="#" class="info-item__item">
            <b>123 Nail Ave, Singapore</b>
          </a>
        </div>
      </div>
      <div class="info-item">
        <img src="<?= getUrl("images/contacts (2).svg") ?>" alt="">
        <div class="info-item__content">
          <a href="#" class="info-item__item">
            <b>friends@sunnynails.sg</b>
            <span>loyalty program</span>
          </a>
          <a href="#" class="info-item__item">
            <b>ceo@sunnynails.sg</b>
            <span>communication between clients
            and management</span>
          </a>
          <a href="#" class="info-item__item">
            <b>friends@sunnynails.sg</b>
            <span>franchise applications</span>
          </a>
          <a href="#" class="info-item__item">
            <b>stars@sunnynails.sg</b>
            <span>resume to HR</span>
          </a>
          <a href="#" class="info-item__item">
            <b>info@sunnynails.sg</b>
            <span>all other questions</span>
          </a>
        </div>
      </div>
      <div class="info-item">
        <img src="<?= getUrl("images/contacts (3).svg") ?>" alt="">
        <div class="info-item__content">
          <a href="#" class="info-item__item">
            <b>+65 1234 5678</b>
          </a>
        </div>
      </div>
    </div>
    <div class="contact-section__map">
      <div class="btn yellow">Get Directions</div>
      <div data-token="AIzaSyDm7mX7x2m6k6jZV9tj8E9ZlD9lD9ZlD9Z" data-center='<?= $cords; ?>' id="map"></div>
    </div>
  </div>
</div>