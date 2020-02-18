<div class="wrap">
    <h1><?= esc_html(get_admin_page_title()); ?></h1>

    <h2>Installation</h2>

    <p>The current supported release <a href="https://github.com/lbryio/lbry-sdk/releases/tag/v0.54.0" target="_blank">can be found here</a>. It contains pre-built binaries for macOS, Debian-based Linux, and Windows.</p>
    <p>For ease of use, our plugin will automatically try to run and start the daemon if installed at the root of your Wordpress install, so its advised you keep it there.</p>
    <p>If you want to have your daemon running at a location other than your Wordpress root, feel free to set up a CRON Job on your server that will start the daemon if its not already running</p>

    <h2>Usage</h2>

    <p>By default, `lbrynet` will provide a JSON-RPC server at `http://localhost:5279`. This is the address our plugin will be expecting to use.</p>
    <p>If curious, The full API is documented <a href="https://lbry.tech/api/sdk" target="_blank">here</a></p>

    <p><a href="https://github.com/lbryio/lbry-sdk/blob/master/README.md" target="_blank">SDK Github</a></p>
    <p><a href="https://lbry.com/" target="_blank">LBRY Home Page</a></p>
</div>
