<?php
namespace MapasCulturais\Entities;

use Doctrine\ORM\Mapping as ORM;
use MapasCulturais\Traits;
use MapasCulturais\App;

/**
 * Space
 * @property-read \MapasCulturais\Entities\Agent $owner The owner of this space
 *
 * @ORM\Table(name="space")
 * @ORM\Entity
 * @ORM\entity(repositoryClass="\MapasCulturais\Repositories\Space")
 * @ORM\HasLifecycleCallbacks
 */
class Space extends \MapasCulturais\Entity
{
    use Traits\EntityOwnerAgent,
        Traits\EntityTypes,
        Traits\EntityMetadata,
        Traits\EntityFiles,
        Traits\EntityAvatar,
        Traits\EntityMetaLists,
        Traits\EntityGeoLocation,
        Traits\EntityTaxonomies,
        Traits\EntityAgentRelation,
        Traits\EntityNested,
        Traits\EntityVerifiable,
        Traits\EntitySoftDelete;


    protected static $validations = array(
        'name' => array(
            'required' => 'O nome do espaço é obrigatório',
            'unique' => 'Já existe um espaço com este nome'
         ),
        'shortDescription' => array(
            'required' => 'A descrição curta é obrigatória',
            'v::string()->length(0,400)' => 'A descrição curta deve ter no máximo 400 caracteres'
        ),
        'type' => array(
            'required' => 'O tipo do espaço é obrigatório',
        ),
        'location' => array(
            'required' => 'A localização do espaço no mapa é obrigatória',
            //'v::allOf(v::key("x", v::numeric()->between(-90,90)),v::key("y", v::numeric()->between(-180,180)))' => 'The space location is not valid'
         )
        //@TODO add validation to property type
    );

    //

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\SequenceGenerator(sequenceName="space_id_seq", allocationSize=1, initialValue=1)
     */
    protected $id;

    /**
     * @var \MapasCulturais\Types\GeoPoint
     *
     * @ORM\Column(name="location", type="point", nullable=false)
     */
    protected $location;

    /**
     * @var _geography
     *
     * @ORM\Column(name="_geo_location", type="geography", nullable=false)
     */
    protected $_geoLocation;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    
    /**
     * @var boolean
     *
     * @ORM\Column(name="public", type="boolean", nullable=false)
     */
    protected $public = false;
    
    /**
     * @var string
     *
     * @ORM\Column(name="short_description", type="text", nullable=true)
     */
    protected $shortDescription;

    /**
     * @var string
     *
     * @ORM\Column(name="long_description", type="text", nullable=true)
     */
    protected $longDescription;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_timestamp", type="datetime", nullable=false)
     */
    protected $createTimestamp;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="smallint", nullable=false)
     */
    protected $status = 1;

     /**
     * @var integer
     *
     * @ORM\Column(name="type", type="smallint", nullable=false)
     */
    protected $_type;
    
    /**
     * @var \MapasCulturais\Entities\EventOccurrence[] Event Occurrences
     *
     * @ORM\OneToMany(targetEntity="MapasCulturais\Entities\EventOccurrence", mappedBy="space", fetch="LAZY", cascade={"remove"})
     */
    protected $eventOccurrences;

    /**
     * @var \MapasCulturais\Entities\Space
     *
     * @ORM\ManyToOne(targetEntity="MapasCulturais\Entities\Space", fetch="LAZY")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     * })
     */
    protected $parent;


    /**
     * @var \MapasCulturais\Entities\Space[] Chield spaces
     *
     * @ORM\OneToMany(targetEntity="MapasCulturais\Entities\Space", mappedBy="parent", fetch="LAZY", cascade={"remove"})
     */
    protected $_children;


    /**
     * @var \MapasCulturais\Entities\Agent
     *
     * @ORM\ManyToOne(targetEntity="MapasCulturais\Entities\Agent", fetch="EAGER")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="agent_id", referencedColumnName="id")
     * })
     */
    protected $owner;

    /**
     * @var integer
     *
     * @ORM\Column(name="agent_id", type="integer", nullable=false)
     */
    protected $_ownerId;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_verified", type="boolean", nullable=false)
     */
    protected $isVerified = false;
    
    
    /**
    * @ORM\OneToMany(targetEntity="MapasCulturais\Entities\SpaceMeta", mappedBy="owner", cascade="remove", orphanRemoval=true)
    */
    protected $__metadata = array();

    public function __construct() {
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
        $this->owner = App::i()->user->profile;
        parent::__construct();
    }

    //============================================================= //
    // The following lines ara used by MapasCulturais hook system.
    // Please do not change them.
    // ============================================================ //

    /** @ORM\PrePersist */
    public function prePersist($args = null){ parent::prePersist($args); }
    /** @ORM\PostPersist */
    public function postPersist($args = null){ parent::postPersist($args); }

    /** @ORM\PreRemove */
    public function preRemove($args = null){ parent::preRemove($args); }
    /** @ORM\PostRemove */
    public function postRemove($args = null){ parent::postRemove($args); }

    /** @ORM\PreUpdate */
    public function preUpdate($args = null){ parent::preUpdate($args); }
    /** @ORM\PostUpdate */
    public function postUpdate($args = null){ parent::postUpdate($args); }
}
