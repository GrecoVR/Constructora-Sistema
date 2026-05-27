<!-- Footer for end section -->
      <div class="row mt-3 border-top">      
        <footer class="pt-3 d-flex justify-content-between">
          <p class="fw-semibold">Taller de Base de Datos</p>
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
    const cdnBase = "https://cdn.jsdelivr.net/npm/bootswatch@5.3.8/dist/";
    
    function updateDropdownUI(themeName) {
        $('#theme-dropdown .dropdown-item').removeClass('active');
        $(`#theme-dropdown .dropdown-item[data-theme="${themeName}"]`).addClass('active');
    }
    
    const savedTheme = localStorage.getItem('bootswatchTheme');
    if (savedTheme) {
        $('#theme-link').attr('href', `${cdnBase}${savedTheme}/bootstrap.min.css`);
        updateDropdownUI(savedTheme);
    } else {
        updateDropdownUI('cerulean');
    }

    $('#theme-dropdown .dropdown-item').on('click', function(e) {
        e.preventDefault();
        const selectedTheme = $(this).data('theme');
        const currentTheme = localStorage.getItem('bootswatchTheme') || 'cerulean';
        
        if (selectedTheme === currentTheme) return;
        
        $('body').addClass('theme-fading');
        
        setTimeout(function() {
          
          const $tempStyle = $('<style id="temp-theme-hide">body { opacity: 0 !important; }</style>').appendTo('head');
          
          $('#theme-link').one('load', function() {

              updateDropdownUI(selectedTheme);
              localStorage.setItem('bootswatchTheme', selectedTheme);
              
              $tempStyle.remove();
              
              $('body').removeClass('theme-fading');
              
              }).attr('href', `${cdnBase}${selectedTheme}/bootstrap.min.css`);

         }, 300); // Matches the 0.3s CSS transition
      });
  
    function changeclass() {
      
      const mclass = $("#main");
      if (mclass.hasClass('col-sm-8')) {
        localStorage.setItem('menushow', 1)
      } else {
        localStorage.setItem('menushow', 0)
      }

      $("#main").toggleClass('col-md-10 col-sm-8');
    }
     /*!
     * Color mode toggler
     */

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
        activeThemeIcon.className = 'bi bi-' + iconOfActiveBtn + ' me-2'
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