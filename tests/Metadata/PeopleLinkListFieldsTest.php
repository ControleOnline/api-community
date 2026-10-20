<?php
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Serializer\Filter\PropertyFilter;
use ControleOnline\Entity\PeopleLink;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
final class PeopleLinkListFieldsTest extends TestCase
{
    public function testOptionalListProjectionRestrictsFieldsWithoutChangingDefaultReads(): void
    {
        $filters=(new ReflectionClass(PeopleLink::class))->getAttributes(ApiFilter::class);
        $selected=array_values(array_filter($filters,fn($filter)=>$filter->getArguments()[0]===PropertyFilter::class));
        self::assertCount(1,$selected);
        $args=$selected[0]->getArguments()['arguments'];
        $filter=new PropertyFilter(parameterName:$args['parameterName'],whitelist:$args['whitelist']);
        $context=['groups'=>['people_link:read']];$filter->apply(Request::create('/people_links'),true,[],$context);
        self::assertArrayNotHasKey('attributes',$context);
        $request=Request::create('/people_links','GET',['listFields'=>['id','linkType','people'=>['id','name','email','peopleMedia']]]);
        $filter->apply($request,true,[],$context);
        self::assertSame(['id','linkType','people'=>['id','name']],$context['attributes']);
        self::assertSame(['people_link:read'],$context['groups']);
    }
}
