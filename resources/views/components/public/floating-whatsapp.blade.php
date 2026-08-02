<a
    href="{{ config('company.whatsapp_url') }}"
    class="floating-wa"
    target="_blank"
    rel="noopener noreferrer"
    aria-label="Chat WhatsApp kami sekarang"
        title="Hubungi kami via WhatsApp"
>
    <x-icon.whatsapp class="w-7 h-7" />
</a>

<style>
    .floating-wa {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        z-index: 90;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 50%;
        background: #25D366;
        color: white;
        box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .floating-wa:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 16px rgba(37, 211, 102, 0.5);
    }
</style>
