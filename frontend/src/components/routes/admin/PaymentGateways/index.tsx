import {SettingsPage} from "../../../common/SettingsPage";
import {PaymentGatewaysSection} from "../../settings/Account/PaymentGatewaysSection.tsx";
import {useGetAccount} from "../../../../queries/useGetAccount.ts";

const AdminPaymentGateways = () => {
    const {data: account} = useGetAccount();

    return (
        <SettingsPage
            title="Payment Gateways"
            description="Platform-wide Paystack and Bachs.io settings. Only Super Admins can enable/disable gateways, switch Test/Live modes, or manage keys."
        >
            <PaymentGatewaysSection accountId={account?.id} isReadOnly={false} />
        </SettingsPage>
    );
};

export default AdminPaymentGateways;
