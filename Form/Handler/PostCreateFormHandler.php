<?php

/*
 * This file is part of the CCDNForum ForumBundle
 *
 * (c) CCDN (c) CodeConsortium <http://www.codeconsortium.com/>
 *
 * Available on github <http://www.github.com/codeconsortium/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CCDNForum\ForumBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

use CCDNForum\ForumBundle\Manager\ManagerInterface;

use CCDNForum\ForumBundle\Entity\Topic;
use CCDNForum\ForumBundle\Entity\Post;

/**
 *
 * @author Reece Fowell <reece@codeconsortium.com>
 * @version 1.0
 */
class PostCreateFormHandler
{
    /** @access protected */
    protected $factory;

    /** @access protected */
    protected $container;

    /** @access protected */
    protected $request;

    /** @access protected */
    protected $manager;

    /** @access protected */
    protected $defaults = array();

    /** @access protected */
    protected $form;

    /**
     *
     * @access public
     * @param FormFactory $factory, ContainerInterface $container, ManagerInterface $manager
     */
    public function __construct(FormFactory $factory, ContainerInterface $container)
    {
        $this->defaults = array();
        $this->factory = $factory;
        $this->container = $container;

        $this->manager = $this->container->get('ccdn_forum_forum.manager.post');

        $this->request = $container->get('request');
    }

    /**
     *
     * @access public
     * @param array $defaults
     * @return self
     */
    public function setDefaultValues(array $defaults = null)
    {
        $this->defaults = array_merge($this->defaults, $defaults);

        return $this;
    }

    /**
     *
     * @access public
     * @return bool
     */
    public function process()
    {
        $this->getForm();

        if ($this->request->getMethod() == 'POST') {
            $formData = $this->form->getData();

            $formData->setCreatedDate(new \DateTime());
            $formData->setCreatedBy($this->defaults['user']);
            $formData->setTopic($this->defaults['topic']);
            $formData->setIsLocked(false);
            $formData->setIsDeleted(false);

            $formData = $this->form->getData();

            // get the current time, and compare to when the post was made.
            $now = new \DateTime();
            $interval = $now->diff($formData->getCreatedDate());

            // if post is less than 15 minutes old, don't add that it was edited.
            if ($interval->format('%i') > 15) {
                $formData->setEditedDate(new \DateTime());
                $formData->setEditedBy($this->defaults['user']);
            }

            // Validate
            if ($this->form->isValid()) {
                $this->onSuccess($this->form->getData());

                return true;
            }

        }

        return false;
    }

    /**
     *
     * @access public
     * @return Form
     */
    public function getForm()
    {
        if (! $this->form) {
            if (! array_key_exists('topic', $this->defaults)) {
                throw new \Exception('Topic must be specified to create a Reply in PostCreateFormHandler');
            }

            $postType = $this->container->get('ccdn_forum_forum.form.type.post');

            $post = new Post();
            $post->setTopic($this->defaults['topic']);

            $this->form = $this->factory->create($postType, $post);

            if ($this->request->getMethod() == 'POST') {
                $this->form->bind($this->request);
            }
        }

        return $this->form;
    }

    /**
     *
     * @access protected
     * @param $entity
     * @return PostManager
     */
    protected function onSuccess($entity)
    {
        return $this->manager->create($entity)->flush();
    }
}
