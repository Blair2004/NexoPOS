# NexoPOS 4.x
NexoPOS 4 is the major upgrade of the version 3.x built from scratch using Laravel, TailwindCSS, Vue and other open-source resources. Based on the experience we had on the version 3.x we've learn from our mistakes and from our customers expectations. NexoPOS 4.x will keep what you've liked on the version 3.x and improve it by adding handy feature.

This version available for free, aims to be more : 

- Extensible & Flexible
- Fast & Secured
- Built Truly for small and large businesses

Same as the version 3.x, NexoPOS 4.x will support modules that will ensure extensibility. For those currently using NexoPOS 3.x there should be a module that will ensure the migration to that major upgrade. 

### ⚠️ Warning ⚠️
As NexoPOS 4.x is built from scratch, none of NexoPOS 3.x extensions are compatible. If you have purchased any NexoPOS 3.x extensions (Gastro, Self Ordering Kiosk), it won't work with NexoPOS 4.x. If you need support about NexoPOS 4.x (if you have an issue), post an issue here on Github. If you have an issue with NexoPOS 3.x or any of the related extension, please contact the support via contact@nexopos.com. NexoPOS 4.x doesn't have **yet** any representatives, don't let anyone doing the installation for you.

## Major Additions & Benefits
Thanks to Laravel, NexoPOS will offer a lot of interesting issue that wasn't possible using CodeIgniter. Most of all we'll have a clear and testable code base, important to keep improving the application without bringing additionnal issues. But also, asynchronous operation will ensure a working application that perform long task behind the scene while the application remains blazing fast. The email wasn't perfect on NexoPOS 3.x, that will be solved on NexoPOS 4.x. Let's recapt the major additions.

- Asynchronous Stock Operation (control avialable stock)
- Asynchronous Notifications (get emailed about exausted, damaged & expired stock)
- Grouped Actions For Components (example bulk change category for selected products)
- Using PHP 7.4 more fast and reliable than PHP 7.1 (required on NexoPOS 3.x)
- Really Responsive UI (to ensure a better support of mobile devices)
- Unit of Measure embedded
- Better Stock Taking Operation
- Improved Stock Transfer with transport Progress (might be provided as a module)
- Offline Service*
- Accurate Stock Report (shows when a product sold has been procured).
- Remote Printing With Nexo Print Server 4.x
- A lot more coming up...

## Installation
Before showing the step to install the application there is prior consideration to have in mind. The current root folder of the application, having the folders "app", "bootstrap", "config"... shouldn't be at the root of your server. If you're using Linux, you should configure apache to use the folder "public" (where the index.php is located) as the RootDocument of the installation. For Windows users, with [laragon](https://laragon.org/), you can also point what is the root directory. This technique prevents a lot of exploits. 

The following installation steps require additionnal skills on using CLI (Command Line Interface), but when we'll release NexoPOS builds, that will be a full installation with all the dependencies. We might also create an installer with a very simplified user interface.

- Make sure to have PHP 7.4 & Apache Configured with required extensions : php-xml, php-mbstring, php-msqli... These are often already provided by virtual server like Laragon, XAMP, WAMP, MAMP.
- [Installing Composer](https://getcomposer.org/download/).
- Install Git (that will be helfpul if you want to contribue or just to download).
- Run the following CLI command on the directory where NexoPOS should be installed : `sudo git clone https://github.com/blair2004/NexoPOS-4x.git`
- Run on the CLI `cd NexoPOS-4x`, if that's the directory name created by the previous step.
- Run on the CLI `composer install`, to install Laravel and all dependencies.
- If the project comes without a .env file, you need to create one. You can use the .env.example that should be available at the root. A quick copy paste command will be to do so `cp .env.example .env`. Then run `php artisan key:generate`
- (Optional) Run on the CLI `npm i` to install JavaScript dependencies if you plan to contribute.
- (Optional) Run `php artisan serve` if you don't have your virtual server pointing to your installation. This will run a php server for development purpose only.

As NexoPOS doesn't have a frontend already, you'll end on the default Laravel page. Access `/do-setup/` to launch the installer.

## Contribution Guidelines
Do you plan to contribute ? That's awesome. We're open to any type of contributions. If you're a developper, you'll start by forking the project and deploying that locally for further tests. If youjust have some ideas, consider posting that as an issue. We'll review the ideas and decide to implement it.

## Documentation

- [Menu API](/readme/menu.md)
- [JavaScript/Classes/FormValidation](/readme/javascript/classes/form-validation.md)


