import {IconUsers, IconBuildingBank, IconLayoutDashboard, IconCalendar, IconReceipt, IconSettings, IconChartBar, IconAlertTriangle, IconFlag, IconMail, IconMailHeart, IconSpeakerphone, IconTrash, IconToggleRight, IconCreditCard, IconPalette, IconMailCheck, IconMailForward} from "@tabler/icons-react";
import {t} from "@lingui/macro";
import {NavItem, BreadcrumbItem} from "../AppLayout/types";
import AppLayout from "../AppLayout";
import {useIsCurrentUserSuperAdmin} from "../../../hooks/useIsCurrentUserSuperAdmin.ts";
import {useGetAdminFeatureFlags} from "../../../queries/useGetAdminFeatureFlags.ts";
import {useGetAdminLifecycleEmails} from "../../../queries/useGetAdminLifecycleEmails.ts";
import {useNavigate} from "react-router";
import {useEffect} from "react";
import {useGetMe} from "../../../queries/useGetMe.ts";
import {FeatureFlag} from "../../../constants/featureFlags.ts";
import {useLicensedFeature} from "../../../ee/licensing/hooks/useLicensedFeature.ts";
import {useGetAccount} from "../../../queries/useGetAccount.ts";

const AdminLayout = () => {
    const isSuperAdmin = useIsCurrentUserSuperAdmin();
    const {data: featureFlags} = useGetAdminFeatureFlags(isSuperAdmin);
    const {isSuccess: lifecycleEmailsAvailable} = useGetAdminLifecycleEmails(isSuperAdmin);
    const {isFetched: isMeFetched} = useGetMe();
    const navigate = useNavigate();
    const audience = useLicensedFeature(FeatureFlag.CRM);
    const {data: account} = useGetAccount();
    const navItems: NavItem[] = [
        {label: t`Admin`},
        {link: '', label: t`Dashboard`, icon: IconLayoutDashboard},
        {link: 'accounts', label: t`Accounts`, icon: IconBuildingBank},
        {link: 'users', label: t`Users`, icon: IconUsers},
        {link: 'events', label: t`Events`, icon: IconCalendar},
        {link: 'orders', label: t`Orders`, icon: IconReceipt},
        {link: 'payouts', label: t`Payouts`, icon: IconBuildingBank},
        {link: 'announcements', label: t`Announcements`, icon: IconSpeakerphone},
        {link: 'deletion-requests', label: t`Deletion Requests`, icon: IconTrash},
        {link: 'configurations', label: t`Configurations`, icon: IconSettings},
        {link: 'branding', label: t`Site Branding`, icon: IconPalette},
        {link: 'payment-gateways', label: t`Payment Gateways`, icon: IconCreditCard},
        {link: 'feature-flags', label: t`Feature Flags`, icon: IconToggleRight, showWhen: () => !!featureFlags?.data?.length},
    ];

    const breadcrumbItems: BreadcrumbItem[] = [
        {
            link: '/admin',
            content: t`Admin Dashboard`
        }
    ];

    useEffect(() => {
        if (isMeFetched && !isSuperAdmin) {
            navigate('/');
        }
    }, [isMeFetched, isSuperAdmin, navigate]);

    if (!isSuperAdmin) {
        return (
            <div id="initial-loader" aria-label="Loading">
                <div className="spinner" />
            </div>
        );
    }

    return (
        <AppLayout
            navItems={navItems}
            breadcrumbItems={breadcrumbItems}
            entityType="organizer"
        />
    );
};

export default AdminLayout;
