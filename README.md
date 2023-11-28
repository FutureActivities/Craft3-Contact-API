# Craft Contact API

Use v2 for Craft 3.

use v3+ for Craft 4.

This plugin adds the ability to send contact forms via a REST API endpoint.
Messages are emailed to the address specified and saved in the CMS.

## Features

- Submit contact form via REST API
- Send to specific users or a generic email address
- Submissions saved in the CMS as a new Contact element
- Export submissions as CSV
- Supports reCaptcha validation

## Usage

    POST /rest/v1/contact
    POST /rest/v1/contact/<entryId>

Where `entryId` points to an entry that contains one of the following fields:

- `emailAddress` - Text field
- `contactDetails` - Matrix field which either has a `email` field OR a field of `detailsValue` which is a Link Field type (third party plugin)

### Expected fields

The following fields are not required but recommended in the post data:

- `fromName` - The name of the person sending the form
- `fromEmail` - The email address of the person sending the form
- `subject` - Contact form subject
- `g-recaptcha-response` - For reCaptcha validation

## Email Template

The plugin comes with a basic HTML email template that just lists all data in the post.
You can override this on a site level by adding the template `_contact.twig` in the root of the sites `templates` folder.

## Attachments

This plugin also supports attachments. The following is an example of sending an attachment:

    var formData = new FormData();
    var fileInput = document.getElementById('upload');
    fileInput.files.forEach(file => {
        formData.append('files[]', file);
    });
    
    formData.append('fromName', this.form.name);
    formData.append('fromEmail', this.form.email);
    formData.append('subject', 'My Email');
    
    axios.post('contact', formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    }).then(response => {
        console.log(response);
    });

## Multisite

When submitting your form data include a `siteId` property with the ID value
of the site you are submitting to.

If using the `/rest/v1/contact/<entryId>` endpoint, the siteId value will also
be used to load the localised entry version.

## Changelog

### v3.1

Added multisite support

### v3.0

Craft 4 & PHP 8 support

### v2.0

Removed dependency on the REST API plugin so this plugin can be used on its own.

### v1.1

Added support for file attachments.
Attachments are saved in the volume you select in the settings and attached to the email.