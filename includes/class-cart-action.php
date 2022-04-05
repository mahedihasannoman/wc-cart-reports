<?php 

/**
 * Class WCCR_Cart_Action
 */
 class WCCR_Cart_Action {

    /**
     * link
     * 
     * @since 1.0.0
     *
     * @var string
     */
    public $link;

    /**
     * label
     * 
     * @since 1.0.0
     *
     * @var string
     */
    public $label;

    /**
     * Color
     * 
     * @since 1.0.0
     *
     * @var string
     */
	public $color;

    /**
     * Constructor
     *
     * @param string $link
     * @param string $label
     * @param string $color
     * 
     * @since 1.0.0
     * 
     * @return null
     */
	public function __construct( $link, $label, $color = '' ) {
		$this->link  = $link;
		$this->label = $label;
		$this->color = $color;
	}

    /**
     * Display action link
     * 
     * @since 1.0.0
     *
     * @return string
     */
	public function display() {
		if ( '' !== $this->color ) {
			$color_style = sprintf( " style='color:#%s' ", $this->color );
		} else {
			$color_style = '';
		}
		$ret = " <a href='" . $this->link . "' " . $color_style . ' >' . __(
				$this->label,
				'wc-cart-reports'
			) . '</a> ';

		return $ret;
	}
 }