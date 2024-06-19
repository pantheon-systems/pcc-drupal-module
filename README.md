# PCX Connect | PCC Integration

Drupal Module for PCC Integration

## Pre-requisite

PCX Connect allows Drupal integration with multiple PCC Sites. As a pre-requisite we need following:

- PCC Site ID & Token
  (Refer to [Managing PCC Sites](#managing-pcc-sites) to know more about how to create a PCC site or access an existing site)

- Google Drive Integrated with Pantheon Content Cloud
  (Refer to [Content Creation Guide](https://pcc.pantheon.io/docs/pantheon-content-cloud-installation-instructions) to
connect Google with PCC Site)

## Managing PCC Sites

PCC Sites are managed by PCC Cli. Refer to [Pantheon Developer Guide](https://pcc.pantheon.io/docs/pcc-cli-setup) to 
setup `pcc-cli` and then we can create PCC Site(s). 

### Creating a PCC Site

1. Create a PCC Site using `pcc-cli` to get the site ID:

    ```pcc site create --url mydomain.com```

    And PCC Site Token as:

    ```pcc token create```

    More details here: [Pantheon Guide | PCC Variables](https://pcc.pantheon.io/docs/required-pcc-variables)

2. Once we have PCC Site ID & Token, we can create / manage Drupal PCC Site at `/admin/structure/pcc_sites`

    ![Create PCC Site](./create-pcc-site.png)

**Note: For Reference PCX Connect module ships an example connected PCC Site.**

## Managing content display of PCC Site

Once the PCC Site is created and added in Drupal, we can display content from PCC Site (the connected Google Drive).

### Creating Listing of the content

To create listing of the content from PCC site, create a view to show `PCC Site - Site Name`. We have the following
fields, filters, sorting and pagination available:

#### Available fields

- ID
- Slug
- Title
- Content
- Snippet
- Tags
- Metadata
- Published Date
- Updated At
- Publishing Level

#### Metadata fields

#### Filtering, Sorting and Pagination

### Creating individual page for each content

### Live Preview of content

## Smart Components Integration | PCC | Submodule
