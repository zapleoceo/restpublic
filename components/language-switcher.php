<?php
// Language switcher component for North Republic website
// Usage: include 'components/language-switcher.php';

require_once __DIR__ . '/../classes/TranslationService.php';

$translationService = new TranslationService();
$currentLanguage = $translationService->getLanguage();
$availableLanguages = $translationService->getAvailableLanguages();
?>

<!-- Language Switcher -->
<div class="language-switcher">
    <div class="language-switcher__current" id="languageCurrent">
        <span class="language-code"><?php echo strtoupper(substr($currentLanguage, 0, 2)); ?></span>
        <svg class="language-arrow" width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
            <path d="M7 10l5 5 5-5z"/>
        </svg>
    </div>
    
    <div class="language-switcher__dropdown" id="languageDropdown">
        <?php foreach ($availableLanguages as $code => $lang): ?>
            <a href="#" class="language-option <?php echo $code === $currentLanguage ? 'active' : ''; ?>" 
               data-language="<?php echo $code; ?>">
                <span class="language-code"><?php echo strtoupper(substr($code, 0, 2)); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<style>
/* Language Switcher Styles */
.language-switcher {
    position: relative;
    display: inline-block;
    z-index: 1000;
}

.language-switcher__current {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 0.75rem;
    background: transparent;
    border: 2px solid transparent;
    border-radius: 20px;
    color: var(--color-text-dark, #333);
    font-weight: 600;
    font-size: var(--text-sm);
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 50px;
    justify-content: center;
}

.language-switcher__current:hover {
    background: var(--color-bg-neutral-dark, #f5f5f5);
    border-color: var(--color-bg-primary, #d4af37);
    color: var(--color-bg-primary, #d4af37);
}

.language-code {
    font-family: 'Roboto Flex', sans-serif;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.language-arrow {
    transition: transform 0.3s ease;
    opacity: 0.7;
}

.language-switcher.open .language-arrow {
    transform: rotate(180deg);
}

.language-switcher__dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--color-bg, #1e1e1e); /* Фон сайта */
    border: 1px solid var(--color-border, #e0e0e0);
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    width: 100%; /* Такая же ширина, как у переключателя */
    margin-top: 0.5rem;
    overflow: hidden;
}

.language-switcher.open .language-switcher__dropdown {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.language-option {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1rem;
    color: var(--color-text-dark, #333);
    text-decoration: none;
    transition: all 0.3s ease;
    border-bottom: 1px solid #5f6362;
}

.language-option:last-child {
    border-bottom: none;
}

.language-option:hover {
    background: var(--color-bg-primary, #d4af37);
    color: var(--color-white, #fff);
}

.language-option.active {
    background: var(--color-bg-primary, #d4af37);
    color: var(--color-white, #fff);
}

.language-option .language-code {
    font-size: var(--text-sm);
    font-weight: 600;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .language-switcher__current {
        padding: 0.4rem 0.6rem;
        font-size: var(--text-sm);
        min-width: 45px;
    }
    
    .language-switcher__dropdown {
        right: 0;
        left: auto;
        min-width: 100px;
    }
    
    .language-option {
        padding: 0.6rem 0.8rem;
    }
    
    .language-option .language-code {
        font-size: var(--text-sm);
    }
}

/* Header integration */
.header-language {
    transform: translate(0, calc(-50% + 0.2rem));
    position: absolute;
    right: calc(var(--gutter) * 2 + 220px); /* Слева от телефона с большим отступом */
    top: 50%;
}

.header-language .language-switcher {
    margin: 0;
}

@media (max-width: 900px) {
    .header-language {
        position: static;
        transform: translateY(-2rem);
        opacity: 0;
        visibility: hidden;
        margin: 0 0 var(--vspace-1) 0;
    }
    
    .menu-is-open .header-language {
        transform: translateY(0);
        opacity: 1;
        visibility: visible;
        transition: all 0.6s var(--ease-quick-out);
        transition-delay: 0.3s;
    }
}
</style>

<script>
// Language Switcher JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const languageSwitcher = document.querySelector('.language-switcher');
    const languageCurrent = document.getElementById('languageCurrent');
    const languageDropdown = document.getElementById('languageDropdown');
    const languageOptions = document.querySelectorAll('.language-option');
    
    if (!languageSwitcher) return;
    
    // Toggle dropdown on hover
    languageSwitcher.addEventListener('mouseenter', function() {
        languageSwitcher.classList.add('open');
    });
    
    languageSwitcher.addEventListener('mouseleave', function() {
        languageSwitcher.classList.remove('open');
    });
    
    // Handle language selection
    languageOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const language = this.dataset.language;
            
            // Update current language display
            const currentCode = languageCurrent.querySelector('.language-code');
            if (currentCode) {
                currentCode.textContent = language.substring(0, 2).toUpperCase();
            }
            
            // Update active state
            languageOptions.forEach(opt => opt.classList.remove('active'));
            this.classList.add('active');
            
            // Close dropdown
            languageSwitcher.classList.remove('open');
            
            // Send request to change language
            changeLanguage(language);
        });
    });
    
    // No need for click outside handler with hover
    
    // Function to change language
    function changeLanguage(language) {
        // Show loading state
        const currentCode = languageCurrent.querySelector('.language-code');
        const originalText = currentCode.textContent;
        currentCode.textContent = '...';
        
        // Send AJAX request
        fetch('/api/language/change.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ language: language })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to apply new language
                window.location.reload();
            } else {
                // Restore original text on error
                currentCode.textContent = originalText;
                console.error('Language change failed:', data.message);
            }
        })
        .catch(error => {
            // Restore original text on error
            currentCode.textContent = originalText;
            console.error('Language change error:', error);
        });
    }
});
</script>
