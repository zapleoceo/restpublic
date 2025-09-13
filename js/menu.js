// Category filtering with animations
document.addEventListener('DOMContentLoaded', function() {
    const categoryBtns = document.querySelectorAll('.category-btn, .header-nav__links a');
    const menuSections = document.querySelectorAll('.menu-section');
    const mobileToggle = document.getElementById('mobileCategoryToggle');
    const mobileNav = document.getElementById('mobileCategoryNav');
    
    console.log('Elements found:');
    console.log('- mobileToggle:', mobileToggle);
    console.log('- mobileNav:', mobileNav);
    console.log('- categoryBtns:', categoryBtns.length);
    console.log('- menuSections:', menuSections.length);
    
    // Mobile category navigation functionality (like header-nav)
    if (mobileToggle) {
        console.log('Mobile toggle button found:', mobileToggle);
        mobileToggle.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Mobile toggle clicked!');
            
            // Toggle button state
            const isOpen = !mobileNav.classList.contains('mobile-nav-hidden');
            
            if (isOpen) {
                // Close menu with animation
                const links = mobileNav.querySelector('.header-nav__links');
                if (links) {
                    links.classList.remove('mobile-links-visible');
                    links.classList.add('mobile-links-hidden');
                }
                
                mobileNav.classList.remove('mobile-nav-opening');
                mobileNav.classList.add('mobile-nav-closing');
                mobileToggle.classList.remove('is-clicked');
                document.body.classList.remove('menu-is-open');
                
                setTimeout(() => {
                    mobileNav.classList.add('mobile-nav-hidden');
                }, 300);
            } else {
                // Open menu with animation
                mobileNav.classList.remove('mobile-nav-hidden');
                mobileNav.classList.add('mobile-nav-visible');
                mobileNav.classList.remove('mobile-nav-opening');
                mobileNav.classList.add('mobile-nav-closing');
                mobileToggle.classList.add('is-clicked');
                document.body.classList.add('menu-is-open');
                
                // Trigger animation
                setTimeout(() => {
                    mobileNav.classList.remove('mobile-nav-closing');
                    mobileNav.classList.add('mobile-nav-opening');
                    
                    // Animate links
                    const links = mobileNav.querySelector('.header-nav__links');
                    if (links) {
                        setTimeout(() => {
                            links.classList.remove('mobile-links-hidden');
                            links.classList.add('mobile-links-visible');
                        }, 150);
                    }
                }, 10);
            }
            
            console.log('Button is-clicked:', mobileToggle.classList.contains('is-clicked'));
            console.log('Menu classes:', mobileNav.className);
        });
    } else {
        console.error('Mobile toggle button not found!');
    }
    
    // Set initial active state
    if (categoryBtns.length > 0) {
        categoryBtns[0].classList.add('active');
    }
    if (menuSections.length > 0) {
        menuSections.forEach((section, index) => {
            if (index === 0) {
                section.classList.remove('menu-section-hidden');
                section.classList.add('active');
                // Анимация появления первой секции
                setTimeout(() => {
                    section.classList.add('animate-in');
                    animateMenuItems(section);
                }, 100);
            } else {
                section.classList.add('menu-section-hidden');
            }
        });
    }

    categoryBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const category = this.dataset.category;
            const li = this.closest('li');
            
            // Update active button
            categoryBtns.forEach(b => {
                b.classList.remove('active');
                const parentLi = b.closest('li');
                if (parentLi) {
                    parentLi.classList.remove('current');
                }
            });
            this.classList.add('active');
            if (li) {
                li.classList.add('current');
            }
            
            // Close mobile category navigation with animation
            const links = mobileNav.querySelector('.header-nav__links');
            if (links) {
                links.classList.remove('mobile-links-visible');
                links.classList.add('mobile-links-hidden');
            }
            
            mobileNav.classList.remove('mobile-nav-opening');
            mobileNav.classList.add('mobile-nav-closing');
            mobileToggle.classList.remove('is-clicked');
            document.body.classList.remove('menu-is-open');
            
            setTimeout(() => {
                mobileNav.classList.add('mobile-nav-hidden');
            }, 300);
            
            // Show/hide sections with animation
            menuSections.forEach(section => {
                if (section.dataset.category === category) {
                    section.classList.remove('menu-section-hidden');
                    section.classList.add('active');
                    section.classList.remove('animate-in');
                    
                    // Анимация появления секции
                    setTimeout(() => {
                        section.classList.add('animate-in');
                        animateMenuItems(section);
                    }, 50);
                } else {
                    section.classList.remove('active');
                    section.classList.remove('animate-in');
                    setTimeout(() => {
                        section.classList.add('menu-section-hidden');
                    }, 300);
                }
            });
        });
    });
    
    // Функция анимации элементов меню
    function animateMenuItems(section) {
        const menuItems = section.querySelectorAll('.menu-list__item');
        menuItems.forEach((item, index) => {
            item.classList.remove('animate-in');
            setTimeout(() => {
                item.classList.add('animate-in');
            }, index * 100); // Задержка между элементами
        });
    }
    
    // Sort functionality - both old sort buttons and new dropdown
    const sortBtns = document.querySelectorAll('.sort-btn');
    const sortDropdownItems = document.querySelectorAll('.sort-dropdown__item');
    
    // Handle old sort buttons (if they exist)
    sortBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const sortType = this.dataset.sort;
            
            // Update active sort button
            sortBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Sort current visible section
            const activeSection = document.querySelector('.menu-section.active');
            if (activeSection) {
                sortMenuItems(activeSection, sortType);
            }
        });
    });
    
    // Handle new sort dropdown items
    sortDropdownItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const sortType = this.dataset.sort;
            const dropdown = this.closest('.sort-dropdown');
            const trigger = dropdown.querySelector('.sort-dropdown__trigger');
            const icon = trigger.querySelector('.sort-dropdown__icon');
            
            // Update active dropdown item
            sortDropdownItems.forEach(i => i.classList.remove('active'));
            this.classList.add('active');
            
            // Update trigger icon based on sort type
            const sortIcons = {
                'popularity': '<path d="M12,21.35L10.55,20.03C5.4,15.36 2,12.27 2,8.5C2,5.41 4.42,3 7.5,3C9.24,3 10.91,3.81 12,5.08C13.09,3.81 14.76,3 16.5,3C19.58,3 22,5.41 22,8.5C22,12.27 18.6,15.36 13.45,20.03L12,21.35Z"/>',
                'price': '<path d="M7,15H9C9,16.08 10.37,17 12,17C13.63,17 15,16.08 15,15C15,13.9 13.96,13.5 11.76,12.97C9.64,12.44 7,11.78 7,9C7,7.21 8.47,5.69 10.5,5.18V3H13.5V5.18C15.53,5.69 17,7.21 17,9H15C15,7.92 13.63,7 12,7C10.37,7 9,7.92 9,9C9,10.1 10.04,10.5 12.24,11.03C14.36,11.56 17,12.22 17,15C17,16.79 15.53,18.31 13.5,18.82V21H10.5V18.82C8.47,18.31 7,16.79 7,15Z"/>',
                'alphabet': '<path d="M14,17H7v-2h7V17z M17,13H7v-2h10V13z M17,9H7V7h10V9z M3,5V3h18v2H3z"/>'
            };
            icon.innerHTML = sortIcons[sortType] || sortIcons['popularity'];
            
            // Sort all menu sections with the selected type
            const menuSections = document.querySelectorAll('.menu-section');
            menuSections.forEach(section => {
                sortMenuItems(section, sortType);
            });
            
            // Close dropdown by removing hover state
            dropdown.classList.remove('hover');
        });
    });
    
    // Function to sort menu items
    function sortMenuItems(section, sortType) {
        const menuList = section.querySelector('.menu-list');
        if (!menuList) return;
        
        const items = Array.from(menuList.querySelectorAll('.menu-list__item'));
        
        items.sort((a, b) => {
            const nameA = a.dataset.productName || a.querySelector('h4').textContent.trim();
            const nameB = b.dataset.productName || b.querySelector('h4').textContent.trim();
            const priceA = parseFloat(a.dataset.price || a.querySelector('.menu-list__item-price').textContent.replace(/[^\d]/g, ''));
            const priceB = parseFloat(b.dataset.price || b.querySelector('.menu-list__item-price').textContent.replace(/[^\d]/g, ''));
            const popularityA = parseInt(a.dataset.popularity || a.dataset.sortOrder || 0);
            const popularityB = parseInt(b.dataset.popularity || b.dataset.sortOrder || 0);
            
            switch(sortType) {
                case 'alphabet':
                    // Сортировка по алфавиту от А до Я
                    return nameA.localeCompare(nameB, 'ru');
                case 'price':
                    // Сортировка по цене - самые дорогие вверху
                    return priceB - priceA;
                case 'popularity':
                    // Сортировка по популярности - используем data-popularity или data-sort-order
                    const popA = parseInt(a.dataset.popularity || a.dataset.sortOrder || 0);
                    const popB = parseInt(b.dataset.popularity || b.dataset.sortOrder || 0);
                    if (popA !== popB) {
                        return popB - popA; // Большее значение = более популярный
                    }
                    // Если популярность одинаковая, сортируем по цене (дорогие вверху)
                    return priceB - priceA;
                default:
                    return 0; // Keep original order
            }
        });
        
        // Clear and re-append sorted items
        menuList.innerHTML = '';
        items.forEach(item => {
            menuList.appendChild(item);
        });
        
        // Re-animate items
        animateMenuItems(section);
    }
});
