      </div> <!-- end of main content -->
<!-- Footer for end section -->
      <div class="row mt-auto border-top">
        <footer class="py-2 d-flex justify-content-between">
          <p class="fw-semibold mt-2">Taller de Base de Datos</p>
          <ul class="nav">
            <li class="nav-item">
              <a class="nav-link text-secondary" aria-current="page" href="#">Grupo-1</a>
            </li>
            <li class="nav-item">
              <a class="nav-link text-secondary" href="#">Gestion 2026</a>
            </li>
          </ul>
        </footer>
      </div>
      <!-- End footer -->
    </div>
    <!-- End container -->
  </main>
  <!-- end main -->
  <div class="offcanvas offcanvas-start" data-bs-scroll="true" tabindex="-1" id="offcanvasExample"
    aria-labelledby="offcanvasExampleLabel">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="offcanvasExampleLabel">Menu</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
     <?php require 'menu.php'; ?>
    </div>
  </div>
  <script>
    const $body = $("body");
    const $themeLink = $("#themeStylesheet");
    const $themeOptions = $(".theme-option");
    const $themeLabel = $("#activeThemeLabel");
    const savedTheme = localStorage.getItem("bootswatch-theme") || "bootstrap";

    updateActiveUI(savedTheme);
    setTheme(savedTheme, false);

    $themeOptions.on("click", function (e) {
      e.preventDefault();
      const theme = $(this).data("theme");
      if ($(this).hasClass("active")) {
        return;
      }
      setTheme(theme, true);
    });

    function getThemeUrl(theme) {
      if (theme === "bootstrap") {
        return `https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css`;
      }
      return `https://cdn.jsdelivr.net/npm/bootswatch@5.3.8/dist/${theme}/bootstrap.min.css`;
    }

    function setTheme(theme, animate = true) {

      const themeUrl = getThemeUrl(theme);
      // Create preload stylesheet
      const preloadLink = document.createElement("link");
      preloadLink.rel = "stylesheet";
      preloadLink.href = themeUrl;
      // Optional smoother transition
      if (animate) {
        $body.addClass("theme-loading");
      }

      preloadLink.onload = function () {

        // Swap only AFTER fully loaded
        $themeLink.attr("href", themeUrl);
        // Cleanup
        preloadLink.remove();
        updateActiveUI(theme);
        localStorage.setItem("bootswatch-theme",theme);
        setTimeout(() => {
          $body.removeClass("theme-loading");
        }, 80);
      };

      preloadLink.onerror = function () {

        preloadLink.remove();
        $body.removeClass("theme-loading");
        console.error(
          "Failed to load theme:",theme);
      };

      document.head.appendChild(preloadLink);
    }

    function updateActiveUI(theme) {

      $themeOptions.removeClass("active");
      const $activeItem = $themeOptions.filter(`[data-theme="${theme}"]`);
      $activeItem.addClass("active");
      const label = $activeItem.text().trim();
      $themeLabel.text(label);
    }

  function changeclass() {

    const mclass = $("#main");
    if (mclass.hasClass('col-sm-8')) {
      localStorage.setItem('menushow', 1)
    } else {
      localStorage.setItem('menushow', 0)
    }

    $("#main").toggleClass('col-lg-10 col-md-9 col-sm-8');
  }

  // Color mode toggler

  (() => {
    'use strict'

    const getStoredTheme = () => localStorage.getItem('theme')
    const setStoredTheme = theme => localStorage.setItem('theme', theme)

    const getPreferredTheme = () => {
      const storedTheme = getStoredTheme()
      if (storedTheme) {
        return storedTheme
      }

      return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }

    const setTheme = theme => {
      if (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.documentElement.setAttribute('data-bs-theme', 'dark')
      } else {
        document.documentElement.setAttribute('data-bs-theme', theme)
      }
    }

    setTheme(getPreferredTheme())

    const showActiveTheme = (theme, focus = false) => {
      const themeSwitcher = document.querySelector('#bd-theme')

      if (!themeSwitcher) {
        return
      }

      const themeSwitcherText = document.querySelector('#bd-theme-text')
      const activeThemeIcon = document.querySelector('#theme-icon-active')
      const btnToActive = document.querySelector(`[data-bs-theme-value="${theme}"]`)
      const iconOfActiveBtn = btnToActive.getAttribute('data-bs-icon-value')

      document.querySelectorAll('[data-bs-theme-value]').forEach(element => {
        element.classList.remove('active')
        element.setAttribute('aria-pressed', 'false')
      })

      btnToActive.classList.add('active')
      btnToActive.setAttribute('aria-pressed', 'true')
      activeThemeIcon.className = 'bi bi-' + iconOfActiveBtn + ' mx-2'
      const themeSwitcherLabel = `${themeSwitcherText.textContent} (${btnToActive.dataset.bsThemeValue})`
      themeSwitcher.setAttribute('aria-label', themeSwitcherLabel)

      if (focus) {
        themeSwitcher.focus()
      }
    }

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
      const storedTheme = getStoredTheme()
      if (storedTheme !== 'light' && storedTheme !== 'dark') {
        setTheme(getPreferredTheme())
      }
    })

    window.addEventListener('DOMContentLoaded', () => {
      showActiveTheme(getPreferredTheme())

      document.querySelectorAll('[data-bs-theme-value]')
        .forEach(toggle => {
          toggle.addEventListener('click', () => {
            const theme = toggle.getAttribute('data-bs-theme-value')
            setStoredTheme(theme)
            setTheme(theme)
            showActiveTheme(theme, true)
          })
        })
    })
  })()
  </script>
</body>
</html>