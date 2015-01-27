<?php

namespace ModelTest\Model;

use Model\Cond\UserCond;
use Model\Entity\UserEntity;
use Model\TagModel;
use Model\ProductModel;
use Model\UserModel;

/**
 * Class CreateTest
 *
 * @package ModelTest\Model
 * @group Model
 */
class JoinTest extends \ModelTest\Model\TestCase
{
    /**
     * @group Model
     * @group itrun
     */
    public function testJoin()
    {
        /**
         * @var TagModel
         */
        $productModel = ProductModel::getInstance();
        $this->assertInstanceOf('Model\ProductModel', $productModel);
        $this->assertInstanceOf('Model\Cond\ProductCond', $productModel->getCond());

        /**
         * @var UserModel
         */
        $userModel = UserModel::getInstance();
        $this->assertInstanceOf('Model\UserModel', $userModel);
        $this->assertInstanceOf('Model\Cond\UserCond', $userModel->getCond());

        $cond = $userModel->getCond()
                ->where(array('email' => 'test@example.com'));
        $userModel->delete($cond);

        /** @var UserEntity $data */
        $data = $userModel->getByEmail('test@example.com');
        $this->assertInstanceOf('Model\Entity\UserEntity', $data);

        $this->assertFalse($data->exists());
        $user = array('email' => 'test@example.com',
                     '_user_info' => array('about' => 'just a test'));
        $userModel->import($user);

        $data = $userModel->getByEmail('test@example.com');
        $this->assertInstanceOf('Model\Entity\UserEntity', $data);
        $this->assertTrue($data->exists());

        $cond = $userModel->getCond()
                ->where(array('email' => 'test@example.com'))
                ->with(UserCond::WITH_USER_INFO);

        $user = $userModel->get($cond);

        $this->assertInstanceOf('Model\Entity\UserInfoEntity', $user->getUserInfo());
    }
}
