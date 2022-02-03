# LBRYPress - Publish from Wordpress to LBRY automatically

This plugin and readme are in early development. Thank you for your patience.

![](https://spee.ch/c/lbry-press-cover.jpg)

## How it works
The LBRYPress plugin communicates with the LBRY network via a locally installed SDK. This allows you to create a channel and then mirror any published posts to it. If there are images or GIFs in your post, they will be uploaded to spee.ch (our blockchain-based image sharing service) automatically during the publishing process. If you update a post, it will also be updated on LBRY. 

## Downloading and installing the Wordpress plugin
First, install the LBRYPress plugin on Wordpress. 

1) Download the [zip file for this repository](https://github.com/lbryio/lbrypress/archive/master.zip).
1) In Wordpress, install the plugin from the zip file. It will show errors until the next steps are completed.

## Downloading and installing LBRY
This will step you through downloading the LBRY SDK, installing it, and running as a system service. 

1) Download the latest LBRY SDK from our [releases page for your OS](https://github.com/lbryio/lbry-sdk/releases):  `wget https://github.com/lbryio/lbry-sdk/releases/download/v0.86.1/lbrynet-linux.zip`
1) Make a new directory in /opt named lbry: `mkdir /opt/lbry`
1) You may need to install Unzip: `sudo apt get install unzip` 
1) Unzip the file here: `unzip lbrynet-linux.zip -d /opt/lbry`
1) To get started, you can run the SDK manually at first. Open a new terminal, `cd /opt/lbry` and run: `./lbrynet start`
Once you do this, the SDK will startup in the current session and sync with the blockchain. Open a new terminal to issue further commands.

### Install LBRY as system service (can skip this step for now)
1) Create a file called lbrynet.service and insert it into `/etc/systemd/system/`:
```
[Unit]
Description="LBRYnet daemon"
After=network.target
[Service]
ExecStart=/opt/lbry/lbrynet start
User=YOURUSERNAME
Group=YOURUSERGROUP
Restart=on-failure
KillMode=process
[Install]
WantedBy=multi-user.target
```

1) Run `sudo systemctl daemon-reload`
1) Start it with: `sudo service lbrynet start`. If you are already running LBRY in the background, issue a `lbrynet stop` command first.

## Funding and preparing your wallet
LBRY will require LBRY Credits (LBC) for the channel creation and publishing process. You can send LBC to this instance from your LBRY app / lbry.tv using the Wallet page > Send Credits. If you need LBC, sign up for a [lbry.tv account](https://lbry.tv) or [email us](mailto:hello@lbry.com). After you send credits, they will be split into smaller amounts to facilitate the publishing process. You can also use an existing LBRY Desktop wallet/ channel by copying the default_wallet file into `~/.local/shared/lbry/lbryum/wallets`. 

1) Go to the LBRYPress plugin page and find your wallet address:

![](/admin/images/wallet-address.jpg)

1) Copy this address and send at least a few credits to it. From the Desktop app/lbry.tv, go to the Wallet page > Send Credits. 
1) We will take the amount you deposited and split it up by a factor of 10. So if you deposited 10 LBC, you'd split it into 100: The decimal point is important, it will throw back an error without the structure of "10.0" 
1) Go to the LBRYnet Directory `cd /opt/lbry/`
1) Run the command: `./lbrynet account fund --amount=10.0 --outputs=100`

## Setting up publishing
Experimental: republishing of images in blog to LBRY: If images or GIFs are used in your posts, they should be reposted to as thumbnails similar to the upload process in the LBRY apps. This feature may not work correctly at this time. 

**Please note: spee.ch channel creation is no longer available and that step can be skiped.**

1) Select the channel you wish as a **Default Publish Channel**. Can change later on a per-post basis.
1) Select the **Default Publish License** you wish to use as your default.
1) Enter 0.001 for **LBC per Publish** (later you can add more as a support if needed).
1) Click **Save Settings**.

![](/admin/images/settings-tab.jpg)

## Setting up your blog publishing channel
If you don't already have a channel, this process will create a channel in your local wallet where your blog posts will be published to. Any available channels will be listed at the top of the **Your Publishable Channels** section on the **Channels** tab.

1) Enter the channel you wish to create and publish under in **New Channel Name**. 
Your channel will be created with a single @ prefix and all spaces and underscores are changed to a dash. Uppercase characters are allowed. Most special characters are removed.
1) Enter an **Amount of LBC to Bid** of 0.001 (current minimum, you can increase the amount or use supports later).
By adding as a support you push your content higher in the search but also keep your LBC fluid and easily moved without needing to abandon your claim.
1) Click **Add New Channel**.

![](/admin/images/add-channel.jpg)

![](/admin/images/channel-create-success.jpg)

Wait a few minutes and do a page refresh, your new channel should now be in the list.

![](/admin/images/new-channel.jpg) 

## Publishing blog posts
When creating a new post (or editing an existing one), you can choose to publish it on LBRY as well. If you do this for an existing post, it will not retain the original date (known issue). 

1) Create your post.
1) At the bottom of the Document menu, find **LBRY Network**, and click **Sync this post on channel**.
1) Select the channel you want to publish it on. 
1) Click Publish. 
1) The plugin will automatically add a link to content on LBRY.
1) Give it a few minutes to publish and be confirmed on the network (there's currently no feedback for this). Check your content at: https://lbry.tv/@ChannelName.

If you edit a post, it will also create an update on the LBRY network.

## Need help?
Email us at [hello@lbry.com](mailto:hello@lbry.com) if you need assistance setting up the LBRYPress plugin. 
