# Hancom Office Connector for Nextcloud app

This app enables users to edit office documents collaboratively from [Nextcloud](https://nextcloud.com) using Hancom Office Online.

## Features

The app allows to:

* Create and edit text documents, spreadsheets, and presentations.
* Collaborative edit of shared documents in real-time.

Supported formats: DOC, DOCX, CELL, XLS, XLSX, SHOW, PPT, PPTX.

## Installing Nextcloud Hancom Office Online integration app

### Online install

The Nextcloud administrator can install the integration app from the in-built application market.
For that go to the user name and select **Apps**.

After that find **Hancom Office** in the list of available applications and install it.

### Manual install

1. Go to the Nextcloud server _custom_apps/_ directory:
    ```
    cd apps/
    ```
2. Get the Nextcloud Hancom Office Online integration app.
There are several ways to do that:

    a. Download the latest signed version from the official store for [Nextcloud](https://apps.nextcloud.com/apps/##id).

    b. Or you can download the latest signed version from the application [release page](https://github.com/hancom-git/hancom-nextcloud/releases) on GitHub.

    c. Or you can clone the application source code and compile it yourself: 
    ```
    git clone https://github.com/hancom-git/hancom-nextcloud.git hancomoffice
    ```

1. Change the owner to update the application right from Nextcloud web interface:
    ```
    chown -R www-data:www-data hancomoffice
    ```
2. In Nextcloud open the `~/settings/apps/disabled` page with _Not enabled_ apps by administrator and click _Enable_ for the **Hancom Office Online** application.

## Configuring Nextcloud Hancom Office Online integration app

You will need an instance of Hancom Office Online. Then you will need to add server address to Nextcloud config whitelist.
```
  'trusted_domains' => array (
    0 => 'mydomain.address.me:8090',
  ),
```

In Nextcloud open the `~/settings/admin/hancomoffice` page with administrative settings for **Hancom Office Online** section.
Enter the following address to connect Hancom Office Online:

```
https://<address>/
```

Where the **address** is the name of the server with the Hancom Office Online installed.
The address must be accessible for the user browser and from the Nextcloud server.
The Nextcloud server address must also be accessible from Hancom Office Online for correct work.

The **Open in Hancom Office Online** action will be added to the file context menu. By default files opens in viewer mode.
