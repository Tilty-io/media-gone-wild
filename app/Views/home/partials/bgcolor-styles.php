<style>
    .media-preview-checkerboard {
        background-color: hsl(0 0% 92%);
        background-image:
            linear-gradient(45deg, rgb(0 0 0 / 30%) 25%, transparent 25%),
            linear-gradient(-45deg, rgb(0 0 0 / 30%) 25%, transparent 25%),
            linear-gradient(45deg, transparent 75%, rgb(0 0 0 / 30%) 75%),
            linear-gradient(-45deg, transparent 75%, rgb(0 0 0 / 30%) 75%);
        background-size: 20px 20px;
        background-position: 0 0, 0 10px, 10px -10px, -10px 0;
    }

    [data-theme="dark"] .media-preview-checkerboard {
        background-color: hsl(220 15% 20%);
        background-image:
            linear-gradient(45deg, rgb(255 255 255 / 30%) 25%, transparent 25%),
            linear-gradient(-45deg, rgb(255 255 255 / 30%) 25%, transparent 25%),
            linear-gradient(45deg, transparent 75%, rgb(255 255 255 / 30%) 75%),
            linear-gradient(-45deg, transparent 75%, rgb(255 255 255 / 30%) 75%);
    }
</style>

