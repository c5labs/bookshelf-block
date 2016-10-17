<?php
/**
 * FileCovers Controller File.
 *
 * @author   Oliver Green <oliver@c5dev.com>
 * @license  See attached license file
 */
namespace Concrete\Package\FileCoversBlock\Block\FileCovers;

use Exception;
use Core;
use FileSet;
use Concrete\Core\Block\BlockController;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Block Controller Class.
 *
 * Show sets of files using thumbnails.
 *
 * @author   Oliver Green <oliver@c5dev.com>
 * @license  See attached license file
 */
class Controller extends BlockController
{
    /**
     * The block types name.
     *
     * @var string
     */
    protected $btName = 'File Covers';

    /**
     * The block types description.
     *
     * @var string
     */
    protected $btDescription = 'Show sets of files using thumbnails.';

    /**
     * The block types handle.
     *
     * @var string
     */
    protected $btHandle = 'file_covers';

    /**
     * The block types default set within the 'add block' fly out panel.
     * 
     * Valid sets included with the core are: 
     * basic, navigation, forms, social & multimedia.
     *
     * Leaving the value as null will add the block type to the 'other' set.
     *
     * @var string
     */
    protected $btDefaultSet = 'multimedia';

    /**
     * The block types table name;
     * If left as null, the blocks handle will be used to form the table name.
     *
     * @var string
     */
    protected $btTable = 'btFileCovers';

    /**
     * The blocks form width.
     *
     * @var string
     */
    protected $btInterfaceWidth = '400';

    /**
     * The blocks form height.
     *
     * @var string
     */
    protected $btInterfaceHeight = '200';

    /**
     * Here you can defined helpers that the blocks add 
     * and edit forms require. They will be loaded automatically.
     * 
     * @var array
     */
    protected $helpers = ['form'];

    /**
     * When block caching is enabled, this means that the block's database record
     * data will also be cached.
     *
     * @var bool
     */
    protected $btCacheBlockRecord = true;

    /**
     *  When block caching is enabled, enabling this boolean means that the output
     *  of the block will be saved and delivered without rendering the view()
     *  function or hitting the database at all.
     *
     * @var bool
     */
    protected $btCacheBlockOutput = true;

    /**
     * When block caching is enabled and output caching is enabled for a block,
     * this is the value in seconds that the cache will last before being refreshed.
     * (specified in seconds).
     *
     * @var bool
     */
    protected $btCacheBlockOutputLifetime = 3600;

    /**
     * This determines whether a block will cache its output on POST. Some blocks
     * can cache their output but must serve uncached output on POST in order to
     * show error messages, etc.
     *
     * @var bool
     */
    protected $btCacheBlockOutputOnPost = false;

    /**
     * Determines whether a block that can cache its output will continue to cache
     * its output even if the current user viewing it is logged in.
     *
     * @var bool
     */
    protected $btCacheBlockOutputForRegisteredUsers = false;

    /**
     * Gets a list of the systems public file sets 
     * and returns the name & ID.
     * 
     * @return array
     */
    public function getPublicFileSets()
    {
        // Get all of the sets.
        $fsl = new \Concrete\Core\File\Set\SetList();
        $fsl->filterByType(FileSet::TYPE_PUBLIC);
        $sets = $fsl->get();

        // Convert them into id => name format.
        $sets_array = array();
        if (count($sets) > 0) {
            foreach ($sets as $set) {
                $sets_array[$set->getFileSetID()] = $set->getFileSetName();
            }
        }
        return $sets_array;
    }

    /**
     * Gets the blocks configured file set IDs.
     * 
     * @return array
     */
    public function getSelectedFileSetIDs()
    {
        $ids = json_decode($this->fsID, true);
        return is_array($ids) ? $ids : [];
    }

    /**
     * Gets the files within the currently selected filesets.
     * 
     * @return array
     */
    protected function getFileSetFiles()
    {
        $files = [];

        foreach ($this->getSelectedFileSetIDs() as $setID) {
            $files = array_merge($files, FileSet::getFilesBySetID($setID));
        }

        return $files;
    }

    /**
     * Generates the cover / thumbnail for a PDF file version.
     * 
     * @param  Version $file
     * @return Imagick
     */
    protected function generateFileCover($file)
    {
        $fr = $file->getFileResource();

        // Load the file resource.
        $im = new \Imagick();
        $im->setResolution(300, 300);
        $im->readImageBlob($fr->read());
        $im->setIteratorIndex(0);
        $im->setImageIndex(0);

        // Get the first page.
        $page = $im->getImage();
        $im->destroy();

        // Set the background colors to white.
        $page->setBackgroundColor(new \ImagickPixel('white'));
        $page->setImageBackgroundColor(new \ImagickPixel('white'));
        $page->setColorspace(\Imagick::COLORSPACE_SRGB);

        // Flatten the page to apply the background color.
        $flat_page = $page->flattenImages();
        $page->destroy();

        // Convert the image to JPEG.
        $flat_page->setImageFormat('jpg');
        $flat_page->scaleImage(400,0);
        $flat_page->setImageCompression(\Imagick::COMPRESSION_JPEG);
        $flat_page->setImageCompressionQuality(70);

        // Export it to string.
        $image = $flat_page->getImageBlob();
        $flat_page->destroy();

        return $image;
    }

    /**
     * Gets the cover / thumbnail path for a given file version.
     * 
     * @param  File $file
     * @return string
     */
    protected function getFileCover($file)
    {
        // Setup the paths.
        $cache_file_name = $this->getCacheFileName($file);
        $cache_path = $this->getCachePath();
        $relative_cache_path = $this->getRelativeCachePath();

        // Create the base cache path if neeed.
        if (! is_dir($cache_path)) {
            if (is_writable(DIR_BASE.'/application/files/cache/')) {
                mkdir($cache_path);
            } else {
                throw new Exception(sprintf('Could not create cache path [%s].', $cache_path));
            }
        }

        // If there is no cache for the request file version, create ite.
        if (! file_exists($cache_path.'/'.$cache_file_name)) {
            if (! is_writable($cache_path)) {
                throw new Exception(t(sprintf('Could not create cover cache, path is not writable [%s]', $cache_path.'/'.$cache_file_name)));
            }
        }
        
        return [
            'relative_path' => $relative_cache_path.'/'.$cache_file_name,
            'path' => $cache_path.'/'.$cache_file_name,
            'cache_exists' => file_exists($cache_path.'/'.$cache_file_name),
        ];
    }

    protected function getCacheFileName($file)
    {
        return $file->getFileID().'-'.$file->getFileVersionID().'.jpg';
    }

    protected function getCachePath($filename = null)
    {
        $path = DIR_BASE.'/application/files/cache/file_covers';

        if ($filename) {
            $path .= '/'.$filename;
        }

        return $path;
    }

    protected function getRelativeCachePath($filename = null)
    {
        $path = DIR_REL.'/application/files/cache/file_covers';
        
        if ($filename) {
            $path .= '/'.$filename;
        }

        return $path;
    }

    /**
     * Gets the cover / thumbnail image & their associated file 
     * versions for the configured file sets files.
     * 
     * @return array
     */
    public function getFiles()
    {
        $files = $this->getFileSetFiles();
        $covers = [];

        foreach ($files as $file) {
            // We only support PDFs at the moment, skip everything else.
            if ('application/pdf' === $file->getMimetype()) {
                $cover = $this->getFileCover($file);
                $covers[] = [
                    'cover_exists' => $cover['cache_exists'],
                    'cover' => $cover['relative_path'],
                    'version' => $file,
                ];
            }
        }

        // We chunk the results into rows.
        return array_chunk($covers, $this->numPerRow);
    }

    public function __destruct()
    {
        ignore_user_abort(true);
        flush();
        ob_flush();

        $cache_path = $this->getCachePath();

        foreach ($this->getFileSetFiles() as $file) {
            $file_name = $this->getCacheFileName($file);
            $path = $this->getCachePath($file_name);

            if (! file_exists($path)) {
                file_put_contents($path, $this->generateFileCover($file));
            }
        }
    }

    /**
     * Runs when the blocks view template is rendered.
     * 
     * @return void
     */
    public function view()
    {   
        $files = [];

        if (extension_loaded('imagick')) {
            $files = $this->getFiles();
        }

        $this->set('files', $files);
    }

    /**
     * Run when the blocks add template is rendered.
     *
     * @return  void
     */
    public function add()
    {
        $this->form();
    }

    /**
     * Run when the blocks edit template is rendered.
     *
     * @return void
     */
    public function edit()
    {
        $this->form();
    }

    /**
     * Called by the add and edit templates are rendered, as they often share logic.
     *
     * @return void
     */
    public function form()
    {
        $this->requireAsset('select2');
        $this->set('available_file_sets', $this->getPublicFileSets());
        $this->set('selected_file_set_ids', $this->getSelectedFileSetIDs());
    }

    /**
     * Run when the add or edit forms are submitted. This should return true if
     * validation is successful or a Concrete\Core\Error\Error() object if it fails.
     *
     * @param  array  $data
     * @return bool|Error
     */
    public function validate(array $data)
    {
        $errors = new \Concrete\Core\Error\Error();

        /**
         * if ('Oliver' !== $data['name']) {
         *     $errors['name'] = "You input the incorrect name.";
         * }.
         */
        if ($errors->has()) {
            return $errors;
        }

        return true;
    }

    /**
     * Run when the block add or edit form is submitted. The variables
     * within the data array are mapped to columns found in the blocks table. Any
     * post-processing of the blocks data before storage should be completed here.
     *
     * @param  array  $data
     * @return
     */
    public function save(array $data)
    {
        $data['fsID'] = json_encode($data['selected_file_set_ids']);
        $data['numPerRow'] = (0 === intval($data['numPerRow']) ? 2 : intval($data['numPerRow']));
        $data['showTitles'] = (! isset($data['showTitles']) ? false : true);

        parent::save($data);
    }

    /**
     * This happens automatically in Concrete5 when versioning blocks and pages.
     *
     * @param  int $newBlockId
     * @return void|BlockRecord
     */
    public function duplicate($newBlockId)
    {
        return parent::duplicate($newBlockId);
    }

    /**
     * Runs when a block is deleted. This may not happen very often since a
     * block is only completed deleted when all versions that reference
     * that block, including the original, have themselves been deleted.
     *
     * @return [type] [description]
     */
    public function delete()
    {
        parent::delete();
    }

    /**
     * Provides text for the page search indexing routine. This method should
     * return simple, unformatted plain text, not HTML.
     *
     * @return string
     */
    public function getSearchableContent()
    {
        return '';
    }

    /**
     * Runs when a block is being exported.
     *
     * @param  SimpleXMLElement $blockNode
     * @return void
     */
    public function export(SimpleXMLElement $blockNode)
    {
        parent::export($blockNode);
    }

    /**
     * Rund when a block is being imported.
     *
     * @param  Page          $page
     * @param  string          $areaHandle
     * @param  SmpleXMLElement $blockNode
     * @return [boid
     */
    public function import($page, $areaHandle, SmpleXMLElement $blockNode)
    {
        parent::import($page, $areaHandle, $blockNode);
    }
}
