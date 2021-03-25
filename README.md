[![Latest Stable Version](https://poser.pugx.org/blair2004/nexopos/v)](//packagist.org/packages/blair2004/nexopos) [![Total Downloads](https://poser.pugx.org/blair2004/nexopos/downloads)](//packagist.org/packages/blair2004/nexopos) [![Latest Unstable Version](https://poser.pugx.org/blair2004/nexopos/v/unstable)](//packagist.org/packages/blair2004/nexopos) [![License](https://poser.pugx.org/blair2004/nexopos/license)](//packagist.org/packages/blair2004/nexopos)

# About NexoPOS 4.x
NexoPOS 4 is a free point of sale system build using Laravel, TailwindCSS, Vue and other open-source resources. This POS System focuses on utilities and functionalities to offer for most businesses all the tools they need to manage better their store. NexoPOS 4.x include a responsive and beautiful dashboard that ease the interaction either on a smartphone, tables or desktops.

# How To Unlock Premium Features
The premium version of NexoPOS 4.x is available on [CodeCanyon](https://codecanyon.net/item/nexopos-4x-pos-crm-inventory-manager/31188619). While purchasing on CodeCanyon, you also get : 

- $40 In credit to [My NexoPOS](https://my.nexopos.com/en/marketplace) balance.
- Access to NexoPOS 4.x marketplace
- Premium Support & Installation Service
- You're eligible for customization
- 
Therefore, __No refunds are allowed__ if the customer discovered later on the limited free version.

[
![screenshot-www youtube com-2020 10 08-11_24_13](https://user-images.githubusercontent.com/5265663/95446877-d62d5800-0958-11eb-822d-9f5997c0805b.jpg)
](https://youtu.be/-eXapKZrcBc)

## Changelog & Feature Announcement
We're frequently discussing about the future of the app (while waiting discussion are allowed). You can join the WhatsApp [group where tips](https://chat.whatsapp.com/KHWgNmfcfJy7SwJiRQTmG8) are shared on NexoPOS 4.x.

## Demo
- Regular : https://v4.nexopos.com
- MultiStore : https://multistore-v4.nexopos.com
- Username : demouser
- Password : 123456

## Documentation
All the documentation for NexoPOS 4.x can be found on [My NexoPOS](https://my.nexopos.com/en/documentation). That includes : 

- [Configuring the environment](https://my.nexopos.com/en/documentation/getting-started/configuring-the-environment)
- [Downloading NexoPOS](https://my.nexopos.com/en/documentation/getting-started/download-and-install)
- [Installing NexoPOS](https://my.nexopos.com/en/documentation/getting-started/installation-wizard)


## Support Terms

1 - The support on NexoPOS 4.x only applies to the information provided by the users while creating an issue. This means we won't either do the installation on your server or check an issue on your server. You're therefore invited not to post your server information while creating an issue. We'll use your explanations to reproduce your issue and therefore to solve it.

2 - If you would like to have a dedicated support. Consider registering as a member to [My NexoPOS platform](https://my.nexopos.com/en/account/checkout/premium).

## Regarding Release Date

NexoPOS 4.x RC-1 (Release Candidate) is scheduled for 15th December 2020. During that time, we're proceeding further test and creating documentation to cover most 
of the features on NexoPOS 4.x.

## Troubleshooting
Here we list the knowns issues and the way around. Not everyone is likely to face these issues as it depends on the used enviroment.

- Error After Database Details (using `php artisan serve`)
If you're serving the project using `php artisan serve`, after setting up the database credentails and having them validated, you migth stop on an infinite
loading page. 

![screenshot-127 0 0 1_8000-2020 10 01-00_33_17](https://user-images.githubusercontent.com/5265663/94781001-17809f00-037e-11eb-9f14-3bf4427054bf.png)

This is caused because during the database setup, the ".env" is updated which cause Laravel to restart the development server and therefore invalidate your session. The way around, is just to refresh the page and you'll end up on the application details section.

## Contribution Guidelines
Do you plan to contribute? That's awesome. We don't have that much developer on it, so we're open to any type of contributions. If you're a developper, you'll start by forking the project and deploying that locally for further tests. If you just have some ideas, consider posting that as an issue. We'll review the ideas and decide to implement it.

