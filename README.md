[![Latest Stable Version](https://poser.pugx.org/blair2004/nexopos/v)](//packagist.org/packages/blair2004/nexopos) [![Total Downloads](https://poser.pugx.org/blair2004/nexopos/downloads)](//packagist.org/packages/blair2004/nexopos) [![Latest Unstable Version](https://poser.pugx.org/blair2004/nexopos/v/unstable)](//packagist.org/packages/blair2004/nexopos) [![License](https://poser.pugx.org/blair2004/nexopos/license)](//packagist.org/packages/blair2004/nexopos)

[![Deploy to DO](https://www.deploytodo.com/do-btn-blue.svg)](https://cloud.digitalocean.com/apps/new?repo=https://github.com/blair2004/NexoPOS-4x/tree/v4.7.x&refcode=ebdb80cb0ec7)

# About NexoPOS 4.x
NexoPOS 4 is a free point of sale system build using Laravel, TailwindCSS, Vue and other open-source resources. This POS System focuses on utilities and functionalities to offer for most businesses all the tools they need to manage better their store. NexoPOS 4.x include a responsive and beautiful dashboard that ease the interaction either on a smartphone, tables or desktops.

## Demo
- Regular : https://v4.nexopos.com
- MultiStore : https://v4-multi.nexopos.com
- Gastro 4.x : https://v4-gastro.nexopos.com
- Username : demouser
- Password : 123456

## Documentation
All the documentation for NexoPOS 4.x can be found on [My NexoPOS](https://my.nexopos.com/en/documentation). That includes : 

- [Configuring the environment](https://my.nexopos.com/en/documentation/getting-started/configuring-the-environment)
- [Downloading NexoPOS](https://my.nexopos.com/en/documentation/getting-started/download-and-install)
- [Installing NexoPOS](https://my.nexopos.com/en/documentation/getting-started/installation-wizard)

# How To Unlock Premium Features
The premium version of NexoPOS 4.x is available on [CodeCanyon](https://codecanyon.net/item/nexopos-4x-pos-crm-inventory-manager/31188619). While purchasing on CodeCanyon, you also get : 

- $40 In credit to [My NexoPOS](https://my.nexopos.com/en/marketplace) balance.
- Access to NexoPOS 4.x marketplace
- Premium Support & Installation Service
- You're eligible for customization

Therefore, __No refunds are allowed__ if the customer discovered later on the limited free version.

[
![screenshot-www youtube com-2020 10 08-11_24_13](https://user-images.githubusercontent.com/5265663/95446877-d62d5800-0958-11eb-822d-9f5997c0805b.jpg)
](https://youtu.be/-eXapKZrcBc)

## Changelog & Feature Announcement
We're frequently discussing about the future of the app (while waiting discussion are allowed). You can join the WhatsApp [group where tips](https://chat.whatsapp.com/KHWgNmfcfJy7SwJiRQTmG8) are shared on NexoPOS 4.x.


## Support Terms

1 - The support on NexoPOS 4.x only applies to the information provided by the users while creating an issue. This means we won't either do the installation on your server or check an issue on your server. You're therefore invited not to post your server information while creating an issue. We'll use your explanations to reproduce your issue and therefore to solve it.

2 - If you would like to have a dedicated support. Consider registering as a member to [My NexoPOS platform](https://my.nexopos.com/en/account/checkout/premium).

## Troubleshooting
Here we list the knowns issues and the way around. Not everyone is likely to face these issues as it depends on the used enviroment.

- CSRF error After Installation
Chances are when you make your installation, you'll have your website URL changed. Unfortunately, if the domain has to change, it must be clearly described on the .env file. We've written a guide that explains how to solve the [CSRF issue on NexoPOS 4.x](https://my.nexopos.com/en/documentation/troubleshooting/how-to-fix-csrf-token-mismatch-on-nexopos-4-x).

- Error After Database Details (using `php artisan serve`)
If you're serving the project using `php artisan serve`, after setting up the database credentails and having them validated, you migth stop on an infinite
loading page. 

![screenshot-127 0 0 1_8000-2020 10 01-00_33_17](https://user-images.githubusercontent.com/5265663/94781001-17809f00-037e-11eb-9f14-3bf4427054bf.png)

This is caused because during the database setup, the ".env" is updated which cause Laravel to restart the development server and therefore invalidate your session. The way around, is just to refresh the page and you'll end up on the application details section.

## Contribution Guidelines
Do you plan to contribute? That's awesome. We don't have that much developer on it, so we're open to any type of contributions. If you're a developper, you'll start by forking the project and deploying that locally for further tests. If you just have some ideas, consider posting that as an issue. We'll review the ideas and decide to implement it.

## Screenshots
The branch 4.7.x introduce the "Dark Mode" which will make working continuously with NexoPOS easier without hurting sight.

### Media Component
The media components help uploading images and managing them. This comes with a field that will ease assigning image to products.
![image](https://user-images.githubusercontent.com/5265663/159091815-5b022ec6-9df4-419b-86f0-85db73ce35c5.png)

### POS Component
The POS is the place where sales are made and handled. The POS is made to provide everything the cashier need to process orders quickly and easilly.
![image](https://user-images.githubusercontent.com/5265663/159092595-3b2e4371-fef4-471c-84cd-b04cb2b7c611.png)

### Orders Components
Every saved sales are listed on the orders list. From there various operation such as opening the receipt, proceeding a refund, making payment can be performed.
![image](https://user-images.githubusercontent.com/5265663/159092684-53a0c41a-d76d-4b69-b737-4420a20b33a1.png)

### Products Components
The resources that are sold are the products. The product UI has been simplified to make sure all necessary details can be added.
![image](https://user-images.githubusercontent.com/5265663/159092753-845b930c-0b4f-4b3d-a42e-8658f74e7499.png)

### Settings Components
Configure how NexoPOS works. From the settings you can configure various section of your application including the currency, the POS features, the orders and much more.
![image](https://user-images.githubusercontent.com/5265663/159092979-267841bc-531d-4a27-b35f-902866fa742a.png)