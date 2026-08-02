import { animate, inView, stagger } from 'motion';
import EmblaCarousel from 'embla-carousel';
import Autoplay from 'embla-carousel-autoplay';

// --- Remove No-JS Fallback ---
document.documentElement.classList.remove('no-js');

// --- Header Scroll State ---
const header = document.querySelector('[data-site-header]');
const updateHeader = () => {
    header?.classList.toggle('is-scrolled', window.scrollY > 40);
};
updateHeader();
window.addEventListener('scroll', updateHeader, { passive: true });

// --- Mobile Menu ---
const menuToggle = document.querySelector('[data-menu-toggle]');
const mobileMenu = document.querySelector('[data-mobile-menu]');

const closeMenu = () => {
    if (!menuToggle || !mobileMenu) return;
    mobileMenu.hidden = true;
    menuToggle.setAttribute('aria-expanded', 'false');
    menuToggle.setAttribute('aria-label', 'Buka menu');
    document.documentElement.classList.remove('menu-open');
    menuToggle.focus();
};

const openMenu = () => {
    if (!menuToggle || !mobileMenu) return;
    mobileMenu.hidden = false;
    menuToggle.setAttribute('aria-expanded', 'true');
    menuToggle.setAttribute('aria-label', 'Tutup menu');
    document.documentElement.classList.add('menu-open');
    mobileMenu.querySelector('a')?.focus();
};

menuToggle?.addEventListener('click', () => {
    const expanded = menuToggle.getAttribute('aria-expanded') === 'true';
    expanded ? closeMenu() : openMenu();
});

mobileMenu?.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
        if (mobileMenu.hidden === false) closeMenu();
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && menuToggle?.getAttribute('aria-expanded') === 'true') {
        closeMenu();
    }
});

// --- Reveal Animation (Motion JS) ---
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (!reducedMotion) {
    inView('[data-reveal]', (element) => {
        animate(
            element,
            {
                opacity: [0, 1],
                transform: ['translateY(30px)', 'translateY(0px)'],
            },
            {
                duration: 0.55,
                ease: [0.21, 0.47, 0.32, 0.98],
            },
        );
    }, {
        margin: '0px 0px -10% 0px',
        amount: 0.08,
    });
} else {
    document.querySelectorAll('[data-reveal]').forEach((element) => {
        element.style.opacity = '1';
        element.style.transform = 'none';
    });
}

// --- Embla Carousel ---
const viewport = document.querySelector('[data-gallery-viewport]');
if (viewport) {
    const carousel = EmblaCarousel(viewport, {
        loop: true,
        align: 'start',
        dragFree: true,
    }, [
        Autoplay({ playInOut: true, delay: 3000, stopOnInteraction: false })
    ]);

    const prevBtn = document.querySelector('[data-gallery-prev]');
    const nextBtn = document.querySelector('[data-gallery-next]');

    const updateBtns = () => {
        if (prevBtn) prevBtn.disabled = !carousel.canScrollPrev();
        if (nextBtn) nextBtn.disabled = !carousel.canScrollNext();
    };

    if (prevBtn) {
        prevBtn.addEventListener('click', () => carousel.scrollPrev());
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => carousel.scrollNext());
    }

    carousel.on('select', updateBtns);
    carousel.on('init', updateBtns);
}

// --- FAQ Accordion ---
const accordions = document.querySelectorAll('[data-accordion-item]');
accordions.forEach((accordion) => {
    const trigger = accordion.querySelector('[data-accordion-trigger]');
    const panel = accordion.querySelector('[data-accordion-panel]');
    const icon = accordion.querySelector('[data-accordion-icon]');

    if (!trigger || !panel || !icon) return;

    trigger.addEventListener('click', () => {
        const isExpanded = trigger.getAttribute('aria-expanded') === 'true';
        
        // Close all others
        accordions.forEach((other) => {
            if (other !== accordion) {
                const otherTrigger = other.querySelector('[data-accordion-trigger]');
                const otherPanel = other.querySelector('[data-accordion-panel]');
                const otherIcon = other.querySelector('[data-accordion-icon]');
                
                otherTrigger?.setAttribute('aria-expanded', 'false');
                otherPanel?.setAttribute('aria-hidden', 'true');
                otherPanel?.classList.remove('grid-rows-[1fr]');
                otherPanel?.classList.add('grid-rows-[0fr]');
                otherIcon?.classList.remove('rotate-180', 'text-phc-primary');
            }
        });

        // Toggle current
        trigger.setAttribute('aria-expanded', !isExpanded);
        panel.setAttribute('aria-hidden', isExpanded);
        
        if (isExpanded) {
            panel.classList.remove('grid-rows-[1fr]');
            panel.classList.add('grid-rows-[0fr]');
            icon.classList.remove('rotate-180', 'text-phc-primary');
        } else {
            panel.classList.remove('grid-rows-[0fr]');
            panel.classList.add('grid-rows-[1fr]');
            icon.classList.add('rotate-180', 'text-phc-primary');
        }
    });
});
