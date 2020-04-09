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

1) Download the latest LBRY SDK from our [releases page for your OS](https://github.com/lbryio/lbry-sdk/releases):  `wget https://github.com/lbryio/lbry-sdk/releases/download/v0.67.2/lbrynet-linux.zip`
1) Make a new directory in /opt named lbry: `mkdir /opt/lbry`
1) Unzip the file here: `unzip lbrynet-linux.zip -d /opt/lbry`
1) To get started, you can run the SDK manually at first. Open a new terminal, `cd /opt/lbry` and run: `./lbrynet start`

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
LBRY will require LBRY Credits (LBC) for the channel creation and publishing process. You can send LBC to this instance from your LBRY app / lbry.tv using the Wallet page > Send Credits. If you need LBC, sign up for a [lbry.tv account](https://lbry.tv) or [email us](mailto:hello@lbry.com). After you send credits, they will be split into smaller amounts to facilatate the publishing process. 

1) Go to the LBRYPress plugin page and find your wallet address:
![](https://spee.ch/d/address.jpg)

1) Copy this address and send at least a few credits to it. From the Desktop app/lbry.tv, go to the Wallet page > Send Credits. 
1) We will take the amount you deposited and split it up by a factor of 10. So if you deposited 10 LBC, you'd split it into 100: `/opt/lbry/lbrynet account fund --amount=10.0 --outputs=100`

## Setting up a spee.ch channel for image re-hosting
If images or GIFs are used in your posts, they'll be reposted to a spee.ch channel and automatically embed the new URL in your blog post. **This channel is not meant to be viewed directly, it's just used as an image repository.** You will create a spee.ch channel and configure the plugin to use it.  

1) Go to https://spee.ch/login and create a new channel / password. 
1) On the plugin page, enter Spee.ch URL as https://spee.ch, and populate the channel/password you just created. 
1) Enter 0.1 for **LBC per Publish**.
1) Click **Save Settings**.

![](https://spee.ch/8/speech-setup-lbrypress.jpeg)

## Setting up a your blog publishing channel
This process will create a channel in your local wallet where your blog posts will be published to. Any available channels will be listed at the top of the **Your Publishable Channels** section.

1) Enter the channel you wish to create and publish under in **New Channel Name**. 
1) Enter a bid of 0.01 (this can be increased later). 
1) Click **Add New Channel**.

![](https://spee.ch/7/channel-lbrypress.jpg)

## Publishing blog posts
When creating a new post (or editing an existing one), you can choose to publish it on LBRY as well. If you do this for an existing post, it will not retain the original date (known issue). 

1) Create your post.
1) At the bottom of the Document menu, find **LBRY Network**, and click **Sync this post on channel**.
1) Select the channel you want to publish it on. 
1) Click Publish. 
1) Give it a few minutes to publish and be confirmed on the network (there's currently no feedback for this). Check your content at: https://lbry.tv/@ChannelName.

## Need help?
Email us at [hello@lbry.com](mailto:hello@lbry.com) if you need assistance setting up the LBRYPress plugin. 
