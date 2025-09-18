document.addEventListener('DOMContentLoaded', function () {
  const partnersDataElement = document.getElementById('partners-data');
  if (!partnersDataElement) {
    return;
  }

  const partnersData = JSON.parse(partnersDataElement.textContent);
  const partnersList = document.querySelector('.partners-list');
  const showMoreBtn = document.querySelector('.partners-show-more');
  const partnersPerPage = 12;
  let currentPage = 1;
  let currentCat = 'all';
  let filteredPartners = partnersData;
  if (!partnersList) {
    return;
  }

  if (!showMoreBtn) {
    return;
  }
  function renderPartners() {
    partnersList.innerHTML = '';
    const end = currentPage * partnersPerPage;
    const partnersToShow = filteredPartners.slice(0, end);
    partnersToShow.forEach((partner) => {
      partnersList.innerHTML += `
        <div class="partner-card" data-cats="${partner.cats.join(' ')}">
          <div class="partner-card__img">
            ${partner.featured ? `<img src="${partner.featured}" alt="">` : ''}
          </div>
          <div class="partner-card__content">
            <div class="partner-card__title">${partner.title}</div>
           <div class="partner-card__desc clamp-3">${
             partner.desc ? partner.desc.split(/<\/p>/i)[0].replace(/(<([^>]+)>)/gi, '') : ''
           }</div>
            ${
              partner.benefit_icon || partner.benefit_title
                ? `
              <div class="partner-card__benefit">
                <div class="partner-card__benefit-icon">
                  ${partner.benefit_icon ? `<img src="${partner.benefit_icon.url}" alt="${partner.benefit_icon.alt}">` : ''}
                </div>
                ${partner.benefit_title ? `<span>${partner.benefit_title.replace(/(<([^>]+)>)/gi, '')}</span>` : ''}
              </div>
            `
                : ''
            }
            <div class="partner-card__btns">
              <button class="partner-card__btn yellow" data-popup="${partner.ID}">${partner.button_text}</button>
              ${
                partner.link
                  ? `<a href="${partner.link}" target="_blank" class="partner-card__btn white"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
                <path d="M14.4273 13.5271L14.5293 5.47019L6.47239 5.57218M13.9627 6.03678L5.69049 14.309" stroke="#85754F" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg></a>`
                  : ''
              }
            </div>
        </div>
      `;
    });
    showMoreBtn.style.display = filteredPartners.length > end ? '' : 'none';
    attachPopupEvents();
  }

  function attachPopupEvents() {
    document.querySelectorAll('.partner-card__btn.yellow').forEach((btn) => {
      btn.onclick = function () {
        const id = this.getAttribute('data-popup');
        let popup = document.getElementById('partner-popup-' + id);
        if (!popup) {
          const partner = partnersData.find((p) => String(p.ID) === String(id));
          let benefitBox = '';
          if (partner.benefit_title || partner.benefit_desc) {
            benefitBox = `
              <div class="partner-popup__benefit-box">
               div class="partner-card__benefit">
               ${partner.benefit_icon ? `<div class="partner-popup__benefit-icon"><img src="${partner.benefit_icon.url}" alt="${partner.benefit_icon.alt}"></div>` : ''}
                ${partner.benefit_title ? `<div class="partner-popup__benefit-title">${partner.benefit_title.replace(/(<([^>]+)>)/gi, '')}</div>` : ''}
              </div>
                ${partner.benefit_desc ? `<div class="partner-popup__benefit-desc">${partner.benefit_desc.replace(/(<([^>]+)>)/gi, '')}</div>` : ''}
              </div>
            `;
          }
          popup = document.createElement('div');
          popup.className = 'partner-popup-backdrop';
          popup.id = 'partner-popup-' + id;
          popup.style.display = 'flex';
          popup.innerHTML = `
            <div class="partner-popup">
              <div class="partner-popup__img">
                ${partner.featured ? `<img src="${partner.featured}" alt="">` : ''}
              </div>
              <div class="partner-popup__content">
                <div class="partner-popup__title">${partner.title}</div>
                <div class="partner-popup__desc">${partner.desc ? partner.desc.replace(/(<([^>]+)>)/gi, '') : ''}</div>
                ${benefitBox}
                ${partner.link ? `<a href="${partner.link}" target="_blank" class="partner-popup__link">Visit the partner’s website</a>` : ''}
              </div>
              <button class="partner-popup__close" type="button"><svg xmlns="http://www.w3.org/2000/svg" width="29" height="29" viewBox="0 0 29 29" fill="none">
                <path d="M13.2197 14.5L5.70327 6.98542C5.61901 6.90116 5.55217 6.80113 5.50657 6.69104C5.46097 6.58095 5.4375 6.46296 5.4375 6.3438C5.4375 6.22464 5.46097 6.10664 5.50657 5.99655C5.55217 5.88646 5.61901 5.78643 5.70327 5.70217C5.78753 5.61791 5.88756 5.55107 5.99765 5.50547C6.10774 5.45987 6.22573 5.4364 6.34489 5.4364C6.46406 5.4364 6.58205 5.45987 6.69214 5.50547C6.80223 5.55107 6.90226 5.61791 6.98652 5.70217L14.5011 13.2186L22.0158 5.70217C22.1859 5.532 22.4167 5.4364 22.6574 5.4364C22.8981 5.4364 23.1288 5.532 23.299 5.70217C23.4692 5.87234 23.5648 6.10314 23.5648 6.3438C23.5648 6.58445 23.4692 6.81525 23.299 6.98542L15.7826 14.5L23.299 22.0147C23.4692 22.1848 23.5648 22.4156 23.5648 22.6563C23.5648 22.897 23.4692 23.1278 23.299 23.2979C23.1288 23.4681 22.8981 23.5637 22.6574 23.5637C22.4167 23.5637 22.1859 23.4681 22.0158 23.2979L14.5011 15.7815L6.98652 23.2979C6.81635 23.4681 6.58555 23.5637 6.34489 23.5637C6.10424 23.5637 5.87344 23.4681 5.70327 23.2979C5.5331 23.1278 5.4375 22.897 5.4375 22.6563C5.4375 22.4156 5.5331 22.1848 5.70327 22.0147L13.2197 14.5Z" fill="#302F34"/>
                </svg></button>
            </div>
          `;
          document.body.appendChild(popup);
        } else {
          popup.style.display = 'flex';
        }
        document.body.classList.add('popup-open');
        popup.querySelector('.partner-popup__close').onclick = function () {
          popup.style.display = 'none';
          document.body.classList.remove('popup-open');
        };
        popup.onclick = function (e) {
          if (e.target === popup) {
            popup.style.display = 'none';
            document.body.classList.remove('popup-open');
          }
        };
      };
    });
  }

  document.querySelectorAll('.partners-filter__btn').forEach((btn) => {
    btn.onclick = function () {
      document
        .querySelectorAll('.partners-filter__btn')
        .forEach((b) => b.classList.remove('active'));
      this.classList.add('active');
      currentCat = this.getAttribute('data-cat');
      currentPage = 1;
      filteredPartners =
        currentCat === 'all'
          ? partnersData
          : partnersData.filter((p) => p.cats.includes(currentCat));
      renderPartners();
    };
  });

  showMoreBtn.onclick = function () {
    currentPage++;
    renderPartners();
  };

  renderPartners();
});
