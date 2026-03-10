<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import ClipboardList from 'lucide-svelte/icons/clipboard-list';
    import Menu from 'lucide-svelte/icons/menu';
    import AppLogo from '@/components/AppLogo.svelte';
    import AppLogoIcon from '@/components/AppLogoIcon.svelte';
    import Breadcrumbs from '@/components/Breadcrumbs.svelte';
    import {
        Avatar,
        AvatarFallback,
        AvatarImage,
    } from '@/components/ui/avatar';
    import { Button } from '@/components/ui/button';
    import {
        DropdownMenu,
        DropdownMenuContent,
        DropdownMenuTrigger,
    } from '@/components/ui/dropdown-menu';
    import {
        NavigationMenu,
        NavigationMenuItem,
        NavigationMenuList,
        navigationMenuTriggerStyle,
    } from '@/components/ui/navigation-menu';
    import {
        Sheet,
        SheetContent,
        SheetHeader,
        SheetTitle,
        SheetTrigger,
    } from '@/components/ui/sheet';
    import UserMenuContent from '@/components/UserMenuContent.svelte';
    import { currentUrlState } from '@/lib/currentUrl';
    import { getInitials } from '@/lib/initials';
    import { toUrl } from '@/lib/utils';
    import { index as staffIntakesIndex } from '@/actions/App/Http/Controllers/Staff/IntakeController';
    import type { BreadcrumbItem, NavItem } from '@/types';

    let {
        breadcrumbs = [],
    }: {
        breadcrumbs?: BreadcrumbItem[];
    } = $props();

    const auth = $derived($page.props.auth);
    const { currentUrl, isCurrentUrl, whenCurrentUrl } = currentUrlState();

    const activeItemStyles =
        'text-foreground';

    const mainNavItems: NavItem[] = [
        {
            title: 'Intakes',
            href: staffIntakesIndex(),
            icon: ClipboardList,
        },
    ];
</script>

<div>
    <div class="border-b border-sidebar-border/80">
        <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
            <!-- Mobile Menu -->
            <div class="lg:hidden">
                <Sheet>
                    <SheetTrigger asChild>
                        {#snippet children(props)}
                            <Button
                                variant="ghost"
                                size="icon"
                                class="mr-2 h-9 w-9"
                                onclick={props.onclick}
                                aria-expanded={props['aria-expanded']}
                            >
                                <Menu class="h-5 w-5" />
                            </Button>
                        {/snippet}
                    </SheetTrigger>
                    <SheetContent side="left" class="w-[300px] p-6">
                        <SheetTitle class="sr-only">Navigation menu</SheetTitle>
                        <SheetHeader class="flex justify-start text-left">
                            <AppLogoIcon
                                class="size-6 fill-current text-foreground"
                            />
                        </SheetHeader>
                        <div
                            class="flex h-full flex-1 flex-col justify-between space-y-4 pt-6 pb-10"
                        >
                            <nav class="-mx-3 space-y-1">
                                {#each mainNavItems as item (toUrl(item.href))}
                                    <Link
                                        href={toUrl(item.href)}
                                        class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent {whenCurrentUrl(
                                            item.href,
                                            $currentUrl,
                                            activeItemStyles,
                                            '',
                                        ) ?? ''}"
                                    >
                                        {#if item.icon}
                                            <item.icon class="h-5 w-5" />
                                        {/if}
                                        {item.title}
                                    </Link>
                                {/each}
                            </nav>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>

            <Link href={toUrl(staffIntakesIndex())} class="flex items-center gap-x-2">
                <AppLogo />
            </Link>

            <!-- Desktop Menu -->
            <div class="hidden h-full lg:flex lg:flex-1">
                <NavigationMenu class="ml-10 flex h-full items-stretch">
                    <NavigationMenuList
                        class="flex h-full items-stretch space-x-2"
                    >
                        {#each mainNavItems as item (toUrl(item.href))}
                            <NavigationMenuItem
                                class="relative flex h-full items-center"
                            >
                                <Link
                                    class="{navigationMenuTriggerStyle()} {whenCurrentUrl(
                                        item.href,
                                        $currentUrl,
                                        activeItemStyles,
                                        '',
                                    ) ?? ''} h-9 cursor-pointer px-4"
                                    href={toUrl(item.href)}
                                >
                                    {#if item.icon}
                                        <item.icon class="mr-2 h-4 w-4" />
                                    {/if}
                                    {item.title}
                                </Link>
                                {#if isCurrentUrl(item.href, $currentUrl)}
                                    <div
                                        class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black"
                                    ></div>
                                {/if}
                            </NavigationMenuItem>
                        {/each}
                    </NavigationMenuList>
                </NavigationMenu>
            </div>

            <div class="ml-auto flex items-center space-x-2">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        {#snippet children(props)}
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-visible:ring-ring"
                                onclick={props.onclick}
                                aria-expanded={props['aria-expanded']}
                                data-state={props['data-state']}
                            >
                                <Avatar
                                    class="size-8 overflow-hidden rounded-full"
                                >
                                    {#if auth.user.avatar}
                                        <AvatarImage
                                            src={auth.user.avatar}
                                            alt={auth.user.name}
                                        />
                                    {/if}
                                    <AvatarFallback
                                        class="rounded-lg bg-muted font-semibold text-foreground"
                                    >
                                        {getInitials(auth.user?.name)}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        {/snippet}
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-56">
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    </div>

    {#if breadcrumbs.length > 1}
        <div class="flex w-full border-b border-sidebar-border/70">
            <div
                class="mx-auto flex h-12 w-full items-center justify-start px-4 text-muted-foreground md:max-w-7xl"
            >
                <Breadcrumbs {breadcrumbs} />
            </div>
        </div>
    {/if}
</div>
