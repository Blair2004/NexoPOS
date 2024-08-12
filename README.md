[![Latest Stable Version](https://poser.pugx.org/blair2004/nexopos/v)](//packagist.org/packages/blair2004/nexopos) [![Total Downloads](https://poser.pugx.org/blair2004/nexopos/downloads)](//packagist.org/packages/blair2004/nexopos) [![Latest Unstable Version](https://poser.pugx.org/blair2004/nexopos/v/unstable)](//packagist.org/packages/blair2004/nexopos) [![License](https://poser.pugx.org/blair2004/nexopos/license)](//packagist.org/packages/blair2004/nexopos)

<p align="center">
  <img src="https://user-images.githubusercontent.com/5265663/162700085-40ed00ca-9154-42cb-850a-ccf1c2db2d5d.png" alt="NexoPOS"/>
</p>

NexoPOS 4 is a free point of sale system build using Laravel, TailwindCSS, Vue and other open-source resources. This POS System focuses on utilities and functionalities to offer for most businesses all the tools they need to manage better their store. NexoPOS include a responsive and beautiful dashboard that ease the interaction either on a smartphone, tables or desktops.

Read Review On:
[![Laravel News](https://user-images.githubusercontent.com/5265663/186377311-c42ddd2c-bc84-465c-a3b6-94e6df8d68bc.jpg)](https://laravel-news.com/nexopos-point-of-sale-for-laravel?utm_source=github.com&utm_medium=readme&utm_campagin=nexopos)

## Demo
- Regular : https://v4.nexopos.com
- MultiStore : https://v4-multi.nexopos.com
- Gastro : https://v4-gastro.nexopos.com
- Username : demouser
- Password : 123456

[![Deploy to DO](https://www.deploytodo.com/do-btn-blue.svg)](https://cloud.digitalocean.com/apps/new?repo=https://github.com/blair2004/NexoPOS/tree/v5.0.x&refcode=ebdb80cb0ec7)

## Documentation
All the documentation for NexoPOS can be found on [My NexoPOS](https://my.nexopos.com/en/documentation). That includes : 

- [Configuring the environment](https://my.nexopos.com/en/documentation/getting-started/configuring-the-environment)
- [Downloading NexoPOS](https://my.nexopos.com/en/documentation/getting-started/download-and-install)
- [Installing NexoPOS](https://my.nexopos.com/en/documentation/getting-started/installation-wizard)

And for developpers, there are more technical tutorials that convers:
- [Creating a module](https://my.nexopos.com/en/documentation/developpers-guides/how-to-create-a-module-for-nexopos-4-x)
- [Create a menu for a module](https://my.nexopos.com/en/documentation/developpers-guides/how-to-create-a-menu-on-nexopos-4-x)
- [Create a route](https://my.nexopos.com/en/documentation/developpers-guides/how-to-register-routes-for-modules)

We've also create video tutorial that will help you performing those easilly.

[
![image](https://user-images.githubusercontent.com/5265663/163531524-408757a8-d5a8-40b1-8e8f-c4e59e778e05.png)
](https://www.youtube.com/watch?v=V80-hOJCywY)

# Get More Using Modules
NexoPOS available on [CodeCanyon](https://codecanyon.net/item/nexopos-4x-pos-crm-inventory-manager/31188619) gives access to premium modules marketplace. While purchasing on CodeCanyon, you get : 

- $40 In credit to [My NexoPOS](https://my.nexopos.com/en/marketplace) balance.
- Access to NexoPOS marketplace
- Premium Support & Installation Service
- You're eligible for customization

## Changelog & Feature Announcement
We're frequently discussing about the future of the app (while waiting discussion are allowed). You can join the WhatsApp [group where tips](https://chat.whatsapp.com/KHWgNmfcfJy7SwJiRQTmG8) are shared on NexoPOS.


## Support Terms

1 - The support on NexoPOS only applies to the information provided by the users while creating an issue. This means we won't either do the installation on your server or check an issue on your server. You're therefore invited not to post your server information while creating an issue. We'll use your explanations to reproduce your issue and therefore to solve it.

2 - If you would like to have a dedicated support. Consider registering as a member to [My NexoPOS platform](https://my.nexopos.com/en/account/checkout/premium).

## Troubleshooting
Here we list the knowns issues and the way around. Not everyone is likely to face these issues as it depends on the used enviroment.

- CSRF error After Installation
Chances are when you make your installation, you'll have your website URL changed. Unfortunately, if the domain has to change, it must be clearly described on the .env file. We've written a guide that explains how to solve the [CSRF issue on NexoPOS](https://my.nexopos.com/en/documentation/troubleshooting/how-to-fix-csrf-token-mismatch-on-nexopos-4-x).

- Error After Database Details (using `php artisan serve`)
If you're serving the project using `php artisan serve`, after setting up the database credentails and having them validated, you migth stop on an infinite
loading page. 

![screenshot-127 0 0 1_8000-2020 10 01-00_33_17](https://user-images.githubusercontent.com/5265663/94781001-17809f00-037e-11eb-9f14-3bf4427054bf.png)

This is caused because during the database setup, the ".env" is updated which cause Laravel to restart the development server and therefore invalidate your session. The way around, is just to refresh the page and you'll end up on the application details section.

## Contribution Guidelines
Do you plan to contribute? That's awesome. We don't have that much developer on it, so we're open to any type of contributions. If you're a developper, you'll start by forking the project and deploying that locally for further tests. Typically, you'll need to build the project (Vue.js) in watch mode. You'll then start by making sure the .env value "NS_ENV" is set to "dev". From there, you can run the following command :

**To install Node.js dependencies**
```
npm install
```

**To watch the Vue compoment changes (Vue.js)**
```
npm run dev
```

**To watch the project changes (TailwindCSS)**
```
npm run css-watch
```

**To build the project for production, you'll need to only run that command:**
This will build the JavaScript file and CSS files.

```
npm run prod
```

## Star History
Thank you for all your support over the years :).

[![Star History Chart](https://api.star-history.com/svg?repos=blair2004/NexoPOS&type=Date)](https://star-history.com/#blair2004/NexoPOS&Date)


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
