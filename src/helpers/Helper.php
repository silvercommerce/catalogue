<?php

namespace SilverCommerce\CatalogueAdmin\Helpers;

use ReflectionClass;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\ViewableData;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Assets\Storage\AssetStore;
use SilverCommerce\CatalogueAdmin\Model\CatalogueProduct;
use SilverCommerce\CatalogueAdmin\Model\CatalogueCategory;

/**
 * Simple helper class to provide common functions across
 * all libraries
 *
 * @author i-lateral (http://www.i-lateral.com)
 * @package catalogue
 */
class Helper extends ViewableData
{
    /**
     * Template names to be removed from the default template list
     *
     * @var array
     * @config
     */
    private static $classes_to_remove = [
        ViewableData::class,
        DataObject::class,
        CatalogueProduct::class,
        CatalogueCategory::class
    ];

    /**
     * Generate an array of classes that can be loaded into a "ClassName" dropdown
     *
     * @param string $base_classname Classname of object we will get list for
     *
     * @return array
     */
    public static function getCreatableClasses($base_classname)
    {
        // Get a list of available product classes
        $instance = singleton($base_classname);
        $classnames = ClassInfo::subclassesFor($base_classname);
        $return = [];

        foreach ($classnames as $classname) {
            // Remove the base level class from the loop
            if ($classname == $base_classname) {
                continue;
            }

            $instance = singleton($classname);
            $description = Config::inst()->get($classname, 'description');

            if (!empty($description)) {
                $description = $instance->i18n_singular_name() . ': ' . $description;
            } else {
                $description = $instance->i18n_singular_name();
            }

            $return[$classname] = $description;
        }

        return $return;
    }

    /**
     * Get a list of templates for rendering
     *
     * @param $classname ClassName to find tempaltes for
     * @return array Array of classnames
     */
    public static function get_templates_for_class($classname)
    {
        $classes = ClassInfo::ancestry($classname);
        $classes = array_reverse($classes);
        $remove_classes = self::config()->classes_to_remove;
        $return = [];

        array_push($classes, "Catalogue", "Page");

        foreach ($classes as $class) {
            if (!in_array($class, $remove_classes)) {
                $return[] = $class;
            }
        }
        
        return $return;
    }

    /**
     * Copy the default no product image from this module and then
     * add a new image to the DB.
     *
     * Returns the new image, so it can be assigned
     */
    public static function generate_no_image()
    {
        // See if the image is already in the DB
        $no_image = "no-image.png";
        $image = File::find($no_image);

        // If not, create new record
        if (!isset($image)) {
            $reflector = new ReflectionClass(self::class);
            $curr_file = dirname($reflector->getFileName());
            $curr_file = str_replace(
                "src/helpers",
                "client/dist/images/no-image.png",
                $curr_file
            );

            $config = [
                'conflict' => AssetStore::CONFLICT_OVERWRITE,
                'visibility' => AssetStore::VISIBILITY_PUBLIC
            ];
            
            $image = Injector::inst()->create(Image::class);
            $image->setFromLocalFile(
                $curr_file,
                $no_image,
                null,
                null,
                $config
            );
            $image->write();
            $image->publishSingle();
        }

        return $image;
    }
}
