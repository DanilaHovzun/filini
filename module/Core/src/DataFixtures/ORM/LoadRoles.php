<?php
namespace Core\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Core\Entity\Role;

class LoadRoles extends AbstractFixture implements DependentFixtureInterface
{

    /**
     * @var array of roles that will be inserted in the database table
     */
    protected $roles = [
        [
            'name' => 'admin',
            'description' => 'A Person who manages users, roles, etc',
            'parent' => null,
            'permissions' => [
                'dashboard.manage',
                'product.manage',
                'category.manage',
                'ourwork.manage',
                'news.manage',
                'page.manage',
                'setting.manage',
                'user.manage',
                'role.manage',
                'permission.manage',
            ]
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->roles as $data) {
            $role = $this->findOrCreateRole($data['name'], $manager);
            /** Check if the object is managed (so already exists in the database) **/
            if (!$manager->contains($role)) {
                $role
                    ->setName($data['name'])
                    ->setDescription($data['description'])
                    ->setParent($data['parent']);

                foreach ($data['permissions'] as $permission) {
                    $referenceName = $permission . '-permission';
                    if ($this->hasReference($referenceName)) {
                        $role->addPermission($this->getReference($referenceName));
                    }
                }
                $manager->persist($role);
            }
            $this->addReference($data['name'] . '-role', $role);
        }

        $manager->flush();
    }

    /**
     * Helper method to return an already existing role from the database, else create and return a new one
     *
     * @param string $name
     * @param ObjectManager $manager
     *
     * @return Core\Entity\Role
     */
    protected function findOrCreateRole($name, ObjectManager $manager)
    {
        return $manager->getRepository(Role::class)->findOneBy(['name' => $name]) ?: new Role();
    }

    /**
     * Fixture classes fixture is dependent on
     *
     * @return array
     */
    public function getDependencies()
    {
        return [
            LoadPermissions::class
        ];
    }
}
