<?php

namespace App\Listener;

use App\Entity\Log;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PostPersist;
use Doctrine\ORM\Mapping\PreUpdate;
use Doctrine\ORM\Mapping\PostUpdate;
use Doctrine\ORM\Mapping\PostRemove;
use Doctrine\ORM\Mapping\PreRemove;
use Doctrine\ORM\Mapping\PreFlush;
use Doctrine\ORM\Mapping\PostLoad;


class LogListener
{

    protected $em;
    protected $entity;
    protected $action;
    protected $log;
    protected $user;

    public function __destruct()
    {

        // foreach ($this->log as $log) {
            /*
            $l = new Log();
            $l->setObject(json_encode($log['object']));
            $l->setUser($this->user);
            $l->setAction($log['action']);
            $l->setClass($log['class']);
            $this->em->persist($l);
            $this->em->flush($l);
            */
        // }
    }
    /** @PrePersist */
    public function prePersistHandler($entity, LifecycleEventArgs $event): void
    {
        /*
        $this->em = $event->getObjectManager();
        $this->entity = $entity;
        $this->action = 'prePersist';
        $this->log();
        */
    }

    /** @PostPersist */
    public function postPersistHandler($entity, LifecycleEventArgs $event): void
    {
        /*
        $this->em = $event->getObjectManager();
        $this->entity = $entity;
        $this->action = 'postPersist';
        $this->log();
        */
    }
    /** @PreUpdate */
    public function preUpdateHandler($entity, LifecycleEventArgs $event): void
    {
        /*
        $this->em = $event->getObjectManager();
        $this->entity = $entity;
        $this->action = 'preUpdate';
        $this->log();
        */
    }
    /** @PostUpdate */
    public function postUpdateHandler($entity, LifecycleEventArgs $event): void
    {
        /*
        $this->em = $event->getObjectManager();
        $this->entity = $entity;
        $this->action = 'postUpdate';
        $this->log();
        */
    }

    /** @PostRemove */
    public function postRemoveHandler($entity, LifecycleEventArgs $event): void
    {
        /*
        $this->em = $event->getObjectManager();
        $this->entity = $entity;
        $this->action = 'postRemove';
        $this->log();
        */
    }
    /** @PreRemove */
    public function preRemoveHandler($entity, LifecycleEventArgs $event): void
    {
        /*
        $this->em = $event->getObjectManager();
        $this->entity = $entity;
        $this->action = 'preRemove';
        $this->log();
        */
    }

    /** @PreFlush */
    public function preFlushHandler($entity, PreFlushEventArgs $event): void
    {
        /*
        $this->em = $event->getObjectManager();
        $this->entity = $entity;
        $this->action = 'preFlush';
        $this->log();
        */
    }
    /** @PostLoad */
    public function postLoadHandler($entity, LifecycleEventArgs $event): void
    {
        /*
     $this->em = $event->getObjectManager();
     $this->entity = $entity;
     $this->action = 'postLoad';
     $this->log();
     */
    }

    private function log()
    {
        if (get_class($this->entity) == 'App\Entity\Webapi\Usuario')
            $this->user = $this->entity;
        else
            $this->log[] =
                [
                    'action' => $this->action,
                    'class' => str_replace('Proxies\\__CG__\\', '', get_class($this->entity)),
                    'object' =>    $this->getObject($this->entity)
                ];
    }

    private function getObject($entity)
    {
        $methods = preg_grep('/^get/', get_class_methods($entity));
        $array = [];
        foreach ($methods as $method) {
            $content = $entity->$method();
            $m = substr($method, 3);
            if (!is_object($content)) {
                $array[$m] = $content;
            } elseif (get_class($content) != 'Doctrine\ORM\PersistentCollection') {
                $array[$m] = $this->findIdentifier($content);
            } else {
                foreach ($content as $c) {
                    $array[$m][] = $this->findIdentifier($c);
                }
            }
        }
        return $array;
    }

    private function findIdentifier($entity)
    {
        $meta = $this->em->getClassMetadata(get_class($entity));
        $identifier = $meta->getSingleIdentifierFieldName();
        $obj = $entity->{'get' . ucfirst($identifier)}();
        if (is_object($obj)) {
            $obj = $this->findIdentifier($obj);
        }
        return $obj;
    }
}
