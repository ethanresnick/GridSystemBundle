<?php
namespace ERD\GridSystemBundle\DependencyInjection;
use Symfony\Component\HttpKernel\Config\FileLocator;
use ERD\GridSystemBundle\Model\CSSObject;
use ERD\GridSystemBundle\Model\CSSObjectMap;
use ERD\GridSystemBundle\Model\Grid;
use ERD\GridSystemBundle\Model\GridMap;
use ERD\GridSystemBundle\Model\GridSystem;
use ERD\GridSystemBundle\Model\GridSystemRepository;

/**
 * Creates a GridSystemRepository from a set of grid configuration files.
 * 
 * Used by the bundle extension to build the master repository service.
 *
 * @author Ethan Resnick Design <hi@ethanresnick.com>
 * @copyright Jun 8, 2012 Ethan Resnick Design
 */
class GridSystemRepositoryFactory
{
    /**
     * The paths to files with grid configuration in them. Paths can be in any format that the
     * Kernel-aware FileLocator in {@link $locator} can handle.
     * @var array[string] 
     */
    protected $paths;
    
    /**
     * @var FileLocator Used to deduce the absolute paths from the paths provided in {@link $paths}.
     */
    protected $locator;
    
    /**
     * The absolute path to the RELAX-NG schema used to validate the grid config files. 
     * 
     * Set in the constructor. 
     */
    protected $configSchema;


    public function __construct(array $filePaths, FileLocator $locator)
    {
        $this->paths   = $filePaths;
        $this->locator = $locator;
        
        $this->configSchema = __DIR__.'/../Resources/config/gridRepositorySchema.xml';
    }
    
    /**
     * The method to call to get a repository object out of the factory.
     * 
     * @return \ERD\GridSystemBundle\Model\GridSystemRepository The configured repository.
     */
    public function getRepository()
    {
        $repo = new GridSystemRepository();
        
        foreach($this->paths as $path)
        {
            try { $file = $this->loadGridFile($path); }
            catch(\Exception $e) { throw new \InvalidArgumentException($e->getMessage()); }

            $gridSystemsWithConfig = $this->processGridFile($file);
                
            foreach($gridSystemsWithConfig as $gridSystemWithConfig)
            {
                $repo->addGridSystem($gridSystemWithConfig[0]);
                $repo->setGridSystemConfig($gridSystemWithConfig[0], $gridSystemWithConfig[1]);
            }
        }

        return $repo;
    }
    
    protected function loadGridFile($path)
    {
        $file = $this->locator->locate($path);
          
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        if (!$dom->load($file, LIBXML_COMPACT)) {
            throw new \InvalidArgumentException(implode("\n", $this->getXmlErrors()));
        }
        $dom->validateOnParse = true;
        $dom->normalizeDocument();
        if(!$dom->relaxNGValidate($this->configSchema))
        {
            $error = libxml_get_last_error();
            throw new \InvalidArgumentException('A grid file you provided ('.$file.') does not validate against
                            the schema in '.$this->configSchema.'. The specific error was: '.$error->message);
        }
        
        return simplexml_import_dom($dom);
    }

    protected function processGridFile(\SimpleXMLElement $gridFile)
    {
        $gridSystems = $gridFile->gridSystem;
        $finalGridSystems = array();
        
        foreach($gridSystems as $system)
        {
            //load grid system
            $attrs = $system->attributes();
            $name    = (string) $attrs->name;
            $minSize = isset($attrs->{'min-text-size'}) ? $attrs->{'min-text-size'} : null;
            $maxSize = isset($attrs->{'max-text-size'}) ? $attrs->{'max-text-size'} : null;
 
            $gs = new GridSystem($name, $minSize, $maxSize);

            //load grid maps
            $gridMaps = array();
            foreach($system->xpath('//grid') as $gridDef)
            {
                $gridAttrs = $this->simplexml_attributes_to_array($gridDef->attributes());
                $gridAttrs = $this->completeGridProperties($gridAttrs);
                
                $grid = new Grid($gridAttrs['unit-count'], $gridAttrs['measure-unit-count'], 
                                 $gridAttrs['gutter-width'], $gridAttrs['padding-width'], 
                                 $gridAttrs['start-text-size'], $gridAttrs['unit-width'], null,
                                 $gridAttrs['margin'],  $gridAttrs['is-one-col']);
                
                $gridMaps[] = new GridMap($grid, array('minTextSize'=>$gridAttrs['start-text-size'], 'maxTextSize'=>$gridAttrs['max-text-size']));
            }
            $gs->setGridMaps($gridMaps);
            
            //load various objects
            foreach($system->xpath('//role') as $roleXML)
            {
                $roleMap = new CSSObjectMap(new CSSObject('.as-'.$roleXML['name']), $gridMaps);
                $roleMap = $this->setDeclarations($roleMap, $roleXML, $gridMaps);
                $gs->addObjectMap($roleMap);
            }

            foreach($system->xpath('//object|//surrounding') as $objectXML)
            {
                $objectMap = new CSSObjectMap(new CSSObject($objectXML['selector']), $gridMaps);
                $objectMap = $this->setDeclarations($objectMap, $objectXML, $gridMaps);
                $gs->addObjectMap($objectMap);
            }
            
            $finalGridSystems[] = array($gs, array(GridSystemRepository::CONFIG_TEMPLATE_KEY=>(string)$attrs->template, GridSystemRepository::CONFIG_OUTPUT_KEY=>(string)$attrs->{'output-path'}));
        }
        
        return $finalGridSystems;
    }
    
    protected function simplexml_attributes_to_array($attributes)
    {
        $result = array();
        foreach($attributes as $k=>$v) 
        {
            $result[$k] = (string) $v;
        }
        
        return $result;
    }

    /**
     * Fills in any missing keys from the grid XML with optional or calculated values.
     * 
     * It's most substantive feature is to calculate column width, gutter width, and padding 
     * width if the grid's defined instead with a desired measure & padding/gutter percentages.
     *
     * @return array The completed set of grid properties ('margin', 'is-one-col', 'start-text-size', 
     * 'max-text-size', 'gutter-width', 'padding-width', 'unit-width', 'unit-count', and 'measure-unit-count')
     */
    protected function completeGridProperties($props)
    {
        if(!isset($props['margin']))        { $props['margin'] = 0; }
        if(!isset($props['is-one-col']))    { $props['is-one-col'] = null; }
        if(!isset($props['max-text-size'])) { $props['max-text-size'] = null; }
                
        if(isset($props['measure-width'])) //we're in measure relative mode and we need to fill in some properties
        {
            $gutterWidth  = $props['gutter-percentage'] * $props['measure-width'];
            $paddingWidth = $props['padding-percentage'] * $props['measure-width'];
            
            
            $totalPadding = ($props['measure-unit-count'] * $paddingWidth * 2);
            $totalGutters = ($props['measure-unit-count']-1) * $gutterWidth;
            $unitWidth    = ($props['measure-width'] - $totalPadding - $totalGutters) / $props['measure-unit-count'];
            
            //round/adjust and save
            $props['gutter-width']  = \round($gutterWidth);
            $props['unit-width']    = ($paddingWidth < \round($paddingWidth)) ? \floor($unitWidth) : \ceil($unitWidth);
            $props['padding-width'] = \round($paddingWidth);
        }    
        
        return $props;
    }   
    
    /**
     * Laods the declarations from the XML into the object map.
     * @param CSSObjectMap $objectMap The object map to load declarations into
     * @param \SimpleXMLElement $xml The XML to extract the declations from
     * @param array[GridMap] The grid maps the object is being being mapped to.
     * @return CSSObjectMap The modified object map. 
     */
    protected function setDeclarations(CSSObjectMap &$objectMap, \SimpleXMLElement $xml, array $gridMaps)
    {
        $declarationSets = $xml->xpath("//declarations");
        
        foreach($declarationSets as $declarationSet)
        {
            $result = array();
            $declarations = $declarationSet->xpath('//declaration');
            foreach($declarations as $declaration)
            {
                $result[(string) $declaration['key']] = (string) $declaration['value'];
            }
            
            if(isset($declarationSet->base) && (boolean) $declarationSet->base === true)
            {
                $objectMap->getObject()->setBaseDeclarations($result);
            }
            elseif(($key = (string) $declarationSet['grid']) && isset($gridMaps[$key]))
            {
                $objectMap->addCSSDeclarationsToMap($gridMaps[$key], $result);
            }
        }
        return $objectMap;
    }    
}