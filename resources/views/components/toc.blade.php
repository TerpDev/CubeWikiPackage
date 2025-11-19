<div
    x-data="{ fixed: false, initialTop: 0, topOffset: 96 }"
    x-init="
        initialTop = $el.getBoundingClientRect().top + window.scrollY;
        window.addEventListener('scroll', () => {
            fixed = window.scrollY + topOffset >= initialTop;
        });
    "
    :style="fixed
        ? 'position: fixed; top: ' + topOffset + 'px;'
        : 'position: static;'
    "
    class="hidden lg:block border-l border-gray-200 dark:border-gray-800 pl-4"
>
    {!! $tocHtml !!}
</div>
