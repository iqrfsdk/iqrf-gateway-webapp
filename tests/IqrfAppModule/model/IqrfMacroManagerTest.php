<?php

/**
 * TEST: App\IqrfAppModule\Model\IqrfMacroManager
 * @covers App\IqrfAppModule\Model\IqrfMacroManager
 * @phpVersion >= 7.0
 * @testCase
 */
declare(strict_types = 1);

namespace Test\IqrfAppModule\Model;

use App\IqrfAppModule\Model\IqrfMacroManager;
use Nette\DI\Container;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Tester\Assert;
use Tester\TestCase;

$container = require __DIR__ . '/../../bootstrap.php';

/**
 * Tests for IQRF IDE macro manager
 */
class IqrfMacroManagerTest extends TestCase {

	/**
	 * @var Container Nette Tester Container
	 */
	private $container;

	/**
	 * @var IqrfMacroManager IQRF IDE Macro manager
	 */
	private $manager;

	/**
	 * @var string File name of IQRF IDE Macros
	 */
	private $macroFileName = __DIR__ . '/../../../iqrf/DPA-macros_180517.iqrfmcr';

	/**
	 * @var string IQRF IDE macros in HEX
	 */
	private $hex = '300D0A300D0A31300D0A436F6F7264696E61746F720D0A547275650D0A300D0A476574206E756D626572206F66204E6F6465730D0A30302E30302E30302E30302E46462E46462E0D0A547275650D0A300D0A46616C73650D0A47657420626F6E646564204E6F6465730D0A30302E30302E30302E30322E46462E46462E0D0A547275650D0A300D0A46616C73650D0A47657420646973636F7665726564204E6F6465730D0A30302E30302E30302E30312E46462E46462E0D0A547275650D0A300D0A46616C73650D0A417574686F72697A6520626F6E640D0A30302E30302E30302E30442E46462E46462E30302E30302E30302E30302E30302E0D0A547275650D0A300D0A46616C73650D0A426F6E64206E6577204E6F64650D0A30302E30302E30302E30342E46462E46462E30302E30302E0D0A547275650D0A300D0A46616C73650D0A52656D6F766520626F6E646564204E6F64650D0A30302E30302E30302E30352E46462E46462E30312E0D0A547275650D0A300D0A547275650D0A52652D626F6E64204E6F64650D0A30302E30302E30302E30362E46462E46462E30312E0D0A547275650D0A300D0A46616C73650D0A436C65617220616C6C20626F6E64730D0A30302E30302E30302E30332E46462E46462E0D0A547275650D0A300D0A547275650D0A52756E20646973636F766572790D0A30302E30302E30302E30372E46462E46462E30372E30302E0D0A547275650D0A300D0A46616C73650D0A53657420686F70730D0A30302E30302E30302E30392E46462E46462E46462E46462E0D0A547275650D0A300D0A46616C73650D0A44504120706172616D733A2074657374696E670D0A30302E30302E30302E30382E46462E46462E30432E0D0A547275650D0A300D0A46616C73650D0A44504120706172616D733A206E6F726D616C0D0A30302E30302E30302E30382E46462E46462E30302E0D0A547275650D0A300D0A46616C73650D0A4E6F64650D0A547275650D0A310D0A52656164204E545720696E666F0D0A30302E30312E30312E30302E46462E46462E0D0A547275650D0A300D0A46616C73650D0A52656D6F766520626F6E640D0A30302E30312E30312E30312E46462E46462E0D0A547275650D0A300D0A547275650D0A4D330D0A0D0A46616C73650D0A300D0A46616C73650D0A4D340D0A0D0A46616C73650D0A300D0A46616C73650D0A456E61626C6520707265626F6E64696E670D0A30302E30312E30312E30342E46462E46462E30372E30312E30302E30302E30302E30302E0D0A547275650D0A300D0A46616C73650D0A5265616420707265626F6E646564204D49440D0A30302E30312E30312E30322E46462E46462E0D0A547275650D0A300D0A46616C73650D0A436C65617220707265626F6E646564204D49440D0A30302E30312E30312E30332E46462E46462E0D0A547275650D0A300D0A46616C73650D0A4D380D0A0D0A46616C73650D0A300D0A46616C73650D0A4D390D0A0D0A46616C73650D0A300D0A46616C73650D0A4D31300D0A0D0A46616C73650D0A300D0A46616C73650D0A4D31310D0A0D0A46616C73650D0A300D0A46616C73650D0A4D31320D0A0D0A46616C73650D0A300D0A46616C73650D0A4F532C205065726970686572616C20696E666F0D0A547275650D0A320D0A5265616420696E666F0D0A30302E30302E30322E30302E46462E46462E0D0A547275650D0A300D0A46616C73650D0A52657365740D0A30302E30302E30322E30312E46462E46462E0D0A547275650D0A300D0A46616C73650D0A52756E20524650474D0D0A30302E30302E30322E30332E46462E46462E0D0A547275650D0A300D0A46616C73650D0A536C6565700D0A30302E30312E30322E30342E46462E46462E30302E30302E30302E0D0A547275650D0A300D0A46616C73650D0A526561642048575020636F6E66696775726174696F6E0D0A30302E30312E30322E30322E46462E46462E0D0A547275650D0A300D0A46616C73650D0A4D360D0A0D0A46616C73650D0A300D0A46616C73650D0A4D370D0A0D0A46616C73650D0A300D0A46616C73650D0A4D380D0A0D0A46616C73650D0A300D0A46616C73650D0A5065726970686572616C20656E756D65726174696F6E0D0A30302E30302E46462E33462E46462E46462E0D0A547275650D0A300D0A46616C73650D0A476574207065726970686572616C20696E666F726D6174696F6E0D0A30302E30302E30302E33462E46462E46462E0D0A547275650D0A300D0A46616C73650D0A47657420696E666F726D6174696F6E20666F72206D6F7265207065726970686572616C730D0A30302E30302E46462E30302E46462E46462E0D0A547275650D0A300D0A46616C73650D0A4D31320D0A0D0A46616C73650D0A300D0A46616C73650D0A4D656D6F726965730D0A547275650D0A330D0A454550524F4D20726561640D0A30302E30302E30332E30302E46462E46462E30302E30352E0D0A547275650D0A300D0A46616C73650D0A454550524F4D2077726974650D0A30302E30302E30332E30312E46462E46462E30302E30312E30322E30332E30342E30352E0D0A547275650D0A300D0A46616C73650D0A4D330D0A0D0A46616C73650D0A300D0A46616C73650D0A4D340D0A0D0A46616C73650D0A300D0A46616C73650D0A45454550524F4D20726561640D0A30302E30302E30342E30322E46462E46462E30302E30302E31302E0D0A547275650D0A300D0A46616C73650D0A45454550524F4D2077726974650D0A30302E30302E30342E30332E46462E46462E30302E30302E30302E30312E30322E30332E30342E30352E30362E30372E30382E30392E30412E30422E30432E30442E30452E30462E0D0A547275650D0A300D0A46616C73650D0A4D370D0A0D0A46616C73650D0A300D0A46616C73650D0A4D380D0A0D0A46616C73650D0A300D0A46616C73650D0A52414D20726561640D0A30302E30302E30352E30302E46462E46462E30302E30352E0D0A547275650D0A300D0A46616C73650D0A52414D2077726974650D0A30302E30302E30352E30312E46462E46462E30302E30312E30322E30332E30342E30352E0D0A547275650D0A300D0A46616C73650D0A4D31310D0A0D0A46616C73650D0A300D0A46616C73650D0A4D31320D0A0D0A46616C73650D0A300D0A46616C73650D0A492F4F2070696E730D0A547275650D0A340D0A53657420616C6C2070696E733A204F5554730D0A30302E30312E30392E30302E46462E46462E30302E32312E30302E30312E31302E30302E30322E46432E30302E0D0A547275650D0A300D0A46616C73650D0A53657420616C6C2070696E733A204C4F570D0A30302E30312E30392E30312E46462E46462E30302E32312E30302E30312E31302E30302E30322E46432E30302E0D0A547275650D0A300D0A46616C73650D0A53657420616C6C2070696E733A20484947480D0A30302E30312E30392E30312E46462E46462E30302E32312E32312E30312E31302E31302E30322E46432E46432E0D0A547275650D0A300D0A46616C73650D0A4D340D0A0D0A46616C73650D0A300D0A46616C73650D0A53657420616C6C2070696E733A20494E730D0A30302E30312E30392E30302E46462E46462E30302E32312E32312E30312E31302E31302E30322E46432E46432E0D0A547275650D0A300D0A46616C73650D0A5365742070696E2043313A204C4F570D0A30302E30312E30392E30312E46462E46462E30302E30312E30302E0D0A547275650D0A300D0A46616C73650D0A5365742070696E2043313A20484947480D0A30302E30312E30392E30312E46462E46462E30302E30312E30312E0D0A547275650D0A300D0A46616C73650D0A50756C7365203173206F6E2043312070696E0D0A30302E30312E30392E30312E46462E46462E30302E30312E30312E46462E45382E30332E30302E30312E30302E0D0A547275650D0A300D0A46616C73650D0A4765742070696E732073746174650D0A30302E30312E30392E30322E46462E46462E0D0A547275650D0A300D0A46616C73650D0A506C6179204444432D494F2D30310D0A30302E30312E30392E30312E46462E46462E30322E30342E30342E46462E39362E30302E30302E30312E30312E46462E39362E30302E30302E32302E32302E30312E31302E31302E30322E34302E34302E46462E39362E30302E30322E30382E30382E46462E39362E30302E30322E31302E31302E46462E39362E30302E30322E41302E41302E46462E45382E30332E30302E32312E30302E30312E31302E30302E30322E46432E30302E0D0A547275650D0A300D0A46616C73650D0A4444432D52452D30313A205245312070756C73652031730D0A30302E30312E30392E30312E46462E46462E30322E30342E30342E46462E45382E30332E30322E30342E30302E0D0A547275650D0A300D0A46616C73650D0A4444432D52452D30313A205245322070756C73652031730D0A30302E30312E30392E30312E46462E46462E30322E41302E41302E46462E45382E30332E30322E41302E30302E0D0A547275650D0A300D0A46616C73650D0A54656D702C20554152542C205350490D0A547275650D0A350D0A4765742074656D70657261747572650D0A30302E30312E30412E30302E46462E46462E0D0A547275650D0A300D0A46616C73650D0A4D320D0A0D0A46616C73650D0A300D0A46616C73650D0A4D330D0A0D0A46616C73650D0A300D0A46616C73650D0A4D340D0A0D0A46616C73650D0A300D0A46616C73650D0A4F70656E20554152543A20393630300D0A30302E30312E30432E30302E46462E46462E30332E0D0A547275650D0A300D0A46616C73650D0A436C6F736520554152540D0A30302E30312E30432E30312E46462E46462E0D0A547275650D0A300D0A46616C73650D0A577269746520554152540D0A30302E30312E30432E30322E46462E46462E46462E34382E36352E36432E36432E36462E0D0A547275650D0A300D0A46616C73650D0A57726974652F7265616420554152540D0A30302E30312E30432E30322E46462E46462E30412E34382E36352E36432E36432E36462E0D0A547275650D0A300D0A46616C73650D0A5772697465205350490D0A30302E30312E30382E30302E46462E46462E46462E34382E36352E36432E36432E36462E0D0A547275650D0A300D0A46616C73650D0A57726974652F72656164205350490D0A30302E30312E30382E30302E46462E46462E30412E34382E36352E36432E36432E36462E0D0A547275650D0A300D0A46616C73650D0A4D31310D0A0D0A46616C73650D0A300D0A46616C73650D0A4D31320D0A0D0A46616C73650D0A300D0A46616C73650D0A4652430D0A547275650D0A360D0A32623A20507265626F6E64696E670D0A30302E30302E30442E30302E46462E46462E30302E30302E30302E0D0A547275650D0A300D0A46616C73650D0A32623A2041636B6E6F776C65646765642062726F6164636173740D0A30302E30302E30442E30302E46462E46462E30322E30352E30362E30332E46462E46462E0D0A547275650D0A300D0A46616C73650D0A53656C656374697665204C454452206F6E0D0A30302E30302E30442E30322E46462E46462E30322E33452E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30352E30362E30312E46462E46462E0D0A547275650D0A300D0A46616C73650D0A457874726120726573756C740D0A30302E30302E30442E30312E46462E46462E0D0A547275650D0A300D0A46616C73650D0A32623A20554152542F53504920646174610D0A30302E30302E30442E30302E46462E46462E30312E30302E30302E0D0A547275650D0A300D0A46616C73650D0A4D360D0A0D0A46616C73650D0A300D0A46616C73650D0A53656C656374697665204C454452206F66660D0A30302E30302E30442E30322E46462E46462E30322E33452E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30302E30352E30362E30302E46462E46462E0D0A547275650D0A300D0A46616C73650D0A5365742046524320726573706F6E73652074696D652032353630206D730D0A30302E30302E30442E30332E46462E46462E34302E0D0A547275650D0A300D0A46616C73650D0A31423A2054656D70657261747572650D0A30302E30302E30442E30302E46462E46462E38302E30302E30302E0D0A547275650D0A300D0A46616C73650D0A31423A2041636B6E6F776C65646765642062726F6164636173740D0A30302E30302E30442E30302E46462E46462E38312E30352E30362E30332E46462E46462E0D0A547275650D0A300D0A46616C73650D0A4D31310D0A0D0A46616C73650D0A300D0A46616C73650D0A4D31320D0A0D0A46616C73650D0A300D0A46616C73650D0A4C45440D0A547275650D0A370D0A536574204C454452206F6E0D0A30302E30302E30362E30312E46462E46462E0D0A547275650D0A300D0A46616C73650D0A536574204C454447206F6E0D0A30302E30302E30372E30312E46462E46462E0D0A547275650D0A300D0A46616C73650D0A50756C7365204C4544520D0A30302E30302E30362E30332E46462E46462E0D0A547275650D0A300D0A46616C73650D0A4D340D0A0D0A46616C73650D0A300D0A46616C73650D0A536574204C454452206F66660D0A30302E30302E30362E30302E46462E46462E0D0A547275650D0A300D0A46616C73650D0A536574204C454447206F66660D0A30302E30302E30372E30302E46462E46462E0D0A547275650D0A300D0A46616C73650D0A50756C7365204C4544470D0A30302E30302E30372E30332E46462E46462E0D0A547275650D0A300D0A46616C73650D0A4D380D0A0D0A46616C73650D0A300D0A46616C73650D0A476574204C4544522073746174650D0A30302E30302E30362E30322E46462E46462E0D0A547275650D0A300D0A46616C73650D0A476574204C4544472073746174650D0A30302E30302E30372E30322E46462E46462E0D0A547275650D0A300D0A46616C73650D0A4D31310D0A0D0A46616C73650D0A300D0A46616C73650D0A4D31320D0A0D0A46616C73650D0A300D0A46616C73650D0A4175746F6E6574776F726B20656D6265646465640D0A547275650D0A380D0A5B435D20454550524F4D20636F6E6669670D0A30302E30302E30332E30312E46462E46462E30302E30372E30382E30332E30302E0D0A547275650D0A300D0A547275650D0A5374617274206175746F6E6574776F726B2070726F636573730D0A30302E30302E30352E30312E46462E46462E30302E30412E0D0A547275650D0A300D0A46616C73650D0A53746F70206175746F6E6574776F726B2070726F636573730D0A30302E30302E30352E30312E46462E46462E30302E38302E0D0A547275650D0A300D0A46616C73650D0A4D340D0A0D0A46616C73650D0A300D0A46616C73650D0A4D350D0A0D0A46616C73650D0A300D0A46616C73650D0A4D360D0A0D0A46616C73650D0A300D0A46616C73650D0A4D370D0A0D0A46616C73650D0A300D0A46616C73650D0A4D380D0A0D0A46616C73650D0A300D0A46616C73650D0A4D390D0A0D0A46616C73650D0A300D0A46616C73650D0A4D31300D0A0D0A46616C73650D0A300D0A46616C73650D0A4D31310D0A0D0A46616C73650D0A300D0A46616C73650D0A4D31320D0A0D0A46616C73650D0A300D0A46616C73650D0A496F542053746172746572204B49540D0A547275650D0A390D0A53452D616C6C2D646174610D0A30302E30312E35452E30312E46462E46462E46462E46462E46462E46462E0D0A547275650D0A300D0A46616C73650D0A4652432D74656D70657261747572652032420D0A30302E30302E30442E30302E46462E46462E45302E35452E30312E30302E30302E0D0A547275650D0A300D0A46616C73650D0A4652432D70686F746F7265736973746F720D0A30302E30302E30442E30302E46462E46462E39302E35452E38312E30302E30302E0D0A547275650D0A300D0A46616C73650D0A4652432D706F74656E74696F6D657465720D0A30302E30302E30442E30302E46462E46462E39302E35452E38312E30312E30302E0D0A547275650D0A300D0A46616C73650D0A5245312D6F66662C5245322D6F66660D0A30302E30322E34422E30302E46462E46462E30432E30302E30302E30302E30302E30302E0D0A547275650D0A300D0A46616C73650D0A5245312D6F6E2C5245322D6F6E0D0A30302E30322E34422E30302E46462E46462E30432E30302E30302E30302E30312E30312E0D0A547275650D0A300D0A46616C73650D0A5245312D6F6E2C5245322D6F66660D0A30302E30322E34422E30302E46462E46462E30432E30302E30302E30302E30312E30302E0D0A547275650D0A300D0A46616C73650D0A5245312D6F66662C5245322D6F6E0D0A30302E30322E34422E30302E46462E46462E30432E30302E30302E30302E30302E30312E0D0A547275650D0A300D0A46616C73650D0A5245312D6F6E0D0A30302E30322E34422E30302E46462E46462E30342E30302E30302E30302E30312E0D0A547275650D0A300D0A46616C73650D0A5245312D6F66660D0A30302E30322E34422E30302E46462E46462E30342E30302E30302E30302E30302E0D0A547275650D0A300D0A46616C73650D0A5245312D6F6E2031730D0A30302E30322E34422E30302E46462E46462E30342E30302E30302E30302E38312E0D0A547275650D0A300D0A46616C73650D0A5245322D6F6E2032730D0A30302E30322E34422E30302E46462E46462E30382E30302E30302E30302E38322E0D0A547275650D0A300D0A46616C73650D0A';

	/**
	 * Condtructor
	 * @param Container $container Nette Tester Container
	 */
	public function __construct(Container $container) {
		$this->container = $container;
	}

	/**
	 * Set up test environment
	 */
	public function setUp() {
		$this->manager = new IqrfMacroManager($this->macroFileName);
	}

	/**
	 * Test function to convert HEX to ASCII
	 */
	public function testHex2Ascii() {
		$expected = FileSystem::read(__DIR__ . '/data/macros-ascii.expected');
		Assert::equal($expected, $this->manager->hex2ascii($this->hex));
	}

	/**
	 * Test function to parse hex of macros
	 */
	public function testParseMacros() {
		$expected = Json::decode(FileSystem::read(__DIR__ . '/data/iqrf-ide-macros.json'), Json::FORCE_ARRAY);
		Assert::equal($expected, $this->manager->parseMacros($this->hex));
	}

	/**
	 * Test function to read IQRF IDE macros
	 */
	public function testRead() {
		$expected = Json::decode(FileSystem::read(__DIR__ . '/data/iqrf-ide-macros.json'), Json::FORCE_ARRAY);
		Assert::equal($expected, $this->manager->read());
	}

}

$test = new IqrfMacroManagerTest($container);
$test->run();
